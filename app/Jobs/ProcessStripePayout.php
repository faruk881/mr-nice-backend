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
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Transfer;

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
        Log::info('Stripe payout job started', [
            'payout_id' => $this->payoutId
        ]);

        $payout = Payout::with('wallet.user')->findOrFail($this->payoutId);
        $wallet = $payout->wallet;
        $courier = $wallet->user;

        /**
         * Prevent re-processing
         */
        if (in_array($payout->status, ['processing', 'completed', 'funded'])) {
            Log::info('Payout already processed', [
                'payout_id' => $payout->id,
                'status' => $payout->status
            ]);
            return;
        }

        /**
         * Ensure courier has connected Stripe account
         */
        if (!$courier->stripe_user_id) {
            $this->failPayout(
                $payout,
                'Courier has no connected Stripe account.'
            );
            return;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {

            /**
             * Transfer: Platform → Courier Stripe balance
             */
            if (!$payout->transfer_id) {

                $amount = (int) round($payout->amount * 100);

                $transfer = Transfer::create(
                    [
                        'amount'      => $amount,
                        'currency'    => strtolower($payout->currency),
                        'destination' => $courier->stripe_user_id,
                        'metadata'    => [
                            'payout_id'  => $payout->id,
                            'courier_id' => $courier->id,
                            'wallet_id'  => $wallet->id,
                        ],
                    ],
                    [
                        'idempotency_key' => $this->transferKey($payout)
                    ]
                );

                $payout->update([
                    'transfer_id' => $transfer->id,
                    'status'      => 'funded',
                ]);

                Log::info('Stripe transfer created', [
                    'payout_id'   => $payout->id,
                    'transfer_id' => $transfer->id,
                ]);
            }

        } catch (ApiErrorException $e) {

            Log::error('Stripe payout error', [
                'payout_id' => $payout->id,
                'type'      => get_class($e),
                'code'      => $e->getStripeCode(),
                'message'   => $e->getMessage(),
            ]);

            /**
             * Platform balance insufficient
             */
            if ($e->getStripeCode() === 'balance_insufficient') {
                $this->failPayout(
                    $payout,
                    'Insufficient platform Stripe balance.'
                );
                return;
            }

            /**
             * Courier Stripe account issues
             */
            if (in_array($e->getStripeCode(), [
                'payouts_not_enabled',
                'account_invalid',
                'bank_account_unverified',
            ])) {

                $this->failPayout(
                    $payout,
                    'Courier payout is not enabled. Please complete Stripe onboarding.'
                );

                return;
            }

            /**
             * Unknown Stripe error → retry job
             */
            throw $e;
        }
    }

    /**
     * Generate idempotency key
     */
    protected function transferKey(Payout $payout): string
    {
        $amount = (int) round($payout->amount * 100);

        return "transfer:payout:{$payout->id}:{$amount}";
    }

    /**
     * Refund wallet and mark payout failed
     */
    protected function failPayout(Payout $payout, string $reason): void
    {
        DB::transaction(function () use ($payout, $reason) {

            $wallet = $payout->wallet;

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'source'         => 'payout_failed',
                'amount'         => $payout->amount,
                'balance_before' => $wallet->balance,
                'balance_after'  => $wallet->balance + $payout->amount,
                'status'         => 'completed',
                'metadata'       => [
                    'payout_id' => $payout->id,
                    'reason'    => 'stripe_failure',
                ],
            ]);

            $wallet->increment('balance', $payout->amount);

            $payout->update([
                'status'         => 'failed',
                'failure_reason' => $reason,
            ]);

        });

        Log::warning('Payout marked as failed', [
            'payout_id' => $payout->id,
            'reason'    => $reason
        ]);
    }
}