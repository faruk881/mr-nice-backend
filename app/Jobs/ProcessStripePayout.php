<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Transfer;
use Stripe\Exception\ApiErrorException;

class ProcessStripePayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $payoutId;

    public function __construct(int $payoutId)
    {
        $this->payoutId = $payoutId;
    }

    public function handle(): void
    {
        Log::info('Stripe payout job started', ['payout_id' => $this->payoutId]);

        Stripe::setApiKey(config('services.stripe.secret'));

        // Lock payout row for safe processing
        $payout = Payout::with('wallet.user')
            ->lockForUpdate()
            ->findOrFail($this->payoutId);

        $wallet = $payout->wallet;
        $courier = $wallet->user;

        // Prevent duplicate processing
        if (in_array($payout->status, ['processing', 'funded', 'paid', 'transferred'])) {
            Log::info('Payout already processed', [
                'payout_id' => $payout->id,
                'status' => $payout->status
            ]);
            return;
        }

        // Ensure courier has connected Stripe account
        if (!$courier->stripe_user_id) {
            $this->failPayout($payout, 'Courier has no connected Stripe account.');
            return;
        }

        try {
            $amount = (int) round($payout->amount * 100); // amount in cents

            // 1️⃣ Create Transfer if not exists
            if (!$payout->transfer_id) {
                $transfer = Transfer::create([
                    'amount' => $amount,
                    'currency' => strtolower($payout->currency),
                    'destination' => $courier->stripe_user_id,
                    'source_transaction' => $payout->charge_id, // optional
                    'metadata' => [
                        'payout_id' => $payout->id,
                        'courier_id' => $courier->id,
                        'wallet_id' => $wallet->id,
                    ],
                ], [
                    'idempotency_key' => "transfer:payout:{$payout->id}:{$amount}"
                ]);

                $payout->update([
                    'transfer_id' => $transfer->id,
                    'status' => 'transferred', // mark as successfully transferred
                ]);

                Log::info('Stripe transfer created', [
                    'payout_id' => $payout->id,
                    'transfer_id' => $transfer->id,
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                ]);
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe transfer error', [
                'payout_id' => $payout->id,
                'code' => $e->getStripeCode(),
                'message' => $e->getMessage()
            ]);

            // Handle insufficient balance
            if (in_array($e->getStripeCode(), ['balance_insufficient', 'insufficient_funds'])) {
                $this->failPayout($payout, 'Platform Stripe balance insufficient.');
                return;
            }

            // Courier Stripe account issues
            if (in_array($e->getStripeCode(), ['account_invalid', 'payouts_not_enabled', 'bank_account_unverified'])) {
                $this->failPayout($payout, 'Courier payout is not enabled. Complete Stripe onboarding.');
                return;
            }

            // Unknown Stripe error → retry job
            throw $e;
        }
    }

    protected function failPayout(Payout $payout, string $reason): void
    {
        DB::transaction(function () use ($payout, $reason) {
            $wallet = $payout->wallet;

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'source' => 'payout_failed',
                'amount' => $payout->amount,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance + $payout->amount,
                'status' => 'completed',
                'metadata' => [
                    'payout_id' => $payout->id,
                    'reason' => 'stripe_failure'
                ]
            ]);

            $wallet->increment('balance', $payout->amount);

            $payout->update([
                'status' => 'failed',
                'failure_reason' => $reason,
            ]);
        });

        Log::warning('Payout failed and wallet refunded', [
            'payout_id' => $payout->id,
            'reason' => $reason
        ]);
    }
}