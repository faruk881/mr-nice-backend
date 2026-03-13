<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class StripeConnectWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Stripe Connect webhook received');

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.connect_webhook_secret');

        // 1️⃣ Verify webhook signature
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload');
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook invalid signature');
            return response('Invalid signature', 400);
        }

        // 2️⃣ Prevent duplicate webhook processing
        $alreadyProcessed = DB::table('stripe_webhook_events')
            ->where('event_id', $event->id)
            ->exists();

        if ($alreadyProcessed) {
            Log::info('Stripe webhook already processed', ['event_id' => $event->id]);
            return response()->json(['status' => 'already_processed']);
        }

        // 3️⃣ Save event to DB
        DB::table('stripe_webhook_events')->insert([
            'event_id'    => $event->id,
            'type'        => $event->type,
            'object_id'   => $event->data->object->id ?? null,
            'object_type' => $event->data->object->object ?? null,
            'payload'     => json_encode($event->data->object),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        Log::info('Stripe webhook processed', ['event_id' => $event->id]);

        // 4️⃣ Route event
        try {
            switch ($event->type) {
                // Account onboarding
                // case 'account.updated':
                //     $this->handleAccountUpdated($event->data->object);
                //     break;

                // Transfer events
                case 'transfer.created':
                case 'transfer.paid':
                case 'transfer.failed':
                case 'transfer.reversed':
                    $this->handleTransferEvents($event);
                    break;
                                // Payout events
                case 'payout.paid':
                case 'payout.failed':
                case 'payout.canceled':
                    $this->handlePayoutEvents($event);
                    break;

                default:
                    Log::info('Unhandled Stripe event', ['type' => $event->type]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'event_type' => $event->type,
                'message'    => $e->getMessage()
            ]);
            return response('Webhook processing failed', 500);
        }

        return response()->json(['status' => 'success']);
    }

    // Update user onboarding status
    protected function handleAccountUpdated($account)
    {
        $user = User::where('stripe_user_id', $account->id)->first();
        if (!$user) return;

        $user->update([
            'stripe_onboarded' => (bool) $account->payouts_enabled
        ]);

        Log::info('Stripe account.updated processed', ['account_id' => $account->id]);
    }

    // Handle payout events
    protected function handlePayoutEvents($event)
    {
        $stripePayout = $event->data->object;
        $payout = Payout::where('stripe_payout_id', $stripePayout->id)->first();
        if (!$payout) return;

        DB::transaction(function () use ($payout, $event) {
            $wallet = $payout->wallet;
            $balanceBefore = $wallet->balance;
            $balanceAfter  = $balanceBefore + $payout->amount;

            switch ($event->type) {
                case 'payout.paid':
                    if ($payout->status !== 'paid') {
                        $payout->update(['status' => 'paid', 'paid_at' => now()]);
                    }
                    break;

                case 'payout.failed':
                    if ($payout->status !== 'failed') {
                        $this->refundWallet($wallet, $payout, 'stripe_payout_failed');
                        $payout->update(['status' => 'failed']);
                    }
                    break;

                case 'payout.canceled':
                    if ($payout->status !== 'cancelled') {
                        $this->refundWallet($wallet, $payout, 'stripe_payout_canceled');
                        $payout->update(['status' => 'cancelled']);
                    }
                    break;
            }
        });
    }

    // Handle transfer events
    protected function handleTransferEvents($event)
    {
        $transfer = $event->data->object;
        $payout = Payout::where('transfer_id', $transfer->id)->first();
        if (!$payout) return;

        DB::transaction(function () use ($payout, $transfer, $event) {
            $wallet = $payout->wallet;
            $balanceBefore = $wallet->balance;
            $balanceAfter  = $balanceBefore + $payout->amount;

            switch ($event->type) {
                case 'transfer.created':
                    if ($payout->status !== 'transfer_initiated') {
                        $payout->update(['status' => 'transfer_initiated']);
                    }
                    break;

                case 'transfer.paid':
                    if ($payout->status !== 'transfered') {
                        $payout->update(['status' => 'transfered']);
                    }
                    break;

                case 'transfer.failed':
                    if ($payout->status !== 'failed') {
                        $this->refundWallet($wallet, $payout, 'stripe_transfer_failed');
                        $payout->update(['status' => 'failed']);
                    }
                    break;

                case 'transfer.reversed':
                    if ($payout->status !== 'reversed') {
                        $this->refundWallet($wallet, $payout, 'stripe_transfer_reversed');
                        $payout->update(['status' => 'reversed']);
                    }
                    break;
            }
        });
    }

    // Refund wallet helper
    protected function refundWallet($wallet, $payout, $reason)
    {
        WalletTransaction::create([
            'wallet_id'      => $wallet->id,
            'type'           => 'credit',
            'source'         => $reason,
            'amount'         => $payout->amount,
            'balance_before' => $wallet->balance,
            'balance_after'  => $wallet->balance + $payout->amount,
            'status'         => 'completed',
            'metadata'       => ['payout_id' => $payout->id, 'reason' => $reason]
        ]);

        $wallet->increment('balance', $payout->amount);
    }
}