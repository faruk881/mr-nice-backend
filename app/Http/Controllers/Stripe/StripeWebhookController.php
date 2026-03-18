<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Notifications\OrderStatusNotification;
use Stripe\Webhook;
use Stripe\StripeClient;
use Exception;

class StripeWebhookController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        Log::info('Stripe webhook received');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;

                case 'payment_intent.succeeded':
                case 'charge.succeeded': // Both events lead to the same success logic
                    $this->handlePaymentSuccess($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe event', ['type' => $event->type]);
            }
        } catch (Exception $e) {
            Log::error('Stripe webhook processing error', [
                'event' => $event->id,
                'message' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Webhook handling failed'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Checkout Session
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

        if (!$payment) {
            Log::warning('Payment not found for session', ['id' => $session->id]);
            return;
        }

        $this->retrieveAndProcess($payment, $session->payment_intent);
    }

    /**
     * Handle PI and Charge Success
     */
    protected function handlePaymentSuccess($object)
    {
        // Normalize the Payment Intent ID
        $piId = ($object->object === 'charge') ? $object->payment_intent : $object->id;

        $payment = Payment::where('stripe_payment_intent_id', $piId)->first();

        if (!$payment) {
            Log::info('Payment record not found for PI', ['pi' => $piId]);
            return;
        }

        $this->retrieveAndProcess($payment, $piId);
    }

    /**
     * Centralized retrieval with expansion and retry logic for fees
     */
    protected function retrieveAndProcess($payment, $paymentIntentId)
    {
        // 1. Retrieve with expansion
        $pi = $this->stripe->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['payment_method', 'latest_charge.balance_transaction']
        ]);

        // 2. Retry once if balance_transaction is still missing (Stripe timing issue)
        if (empty($pi->latest_charge->balance_transaction)) {
            sleep(2);
            $pi = $this->stripe->paymentIntents->retrieve($paymentIntentId, [
                'expand' => ['payment_method', 'latest_charge.balance_transaction']
            ]);
        }

        $this->processSuccess($payment, $pi);
    }

    /**
     * Finalize the database records
     */
    protected function processSuccess($payment, $paymentIntent)
    {
        if ($payment->status === 'succeeded') {
            return;
        }

        DB::transaction(function () use ($payment, $paymentIntent) {
            $charge = $paymentIntent->latest_charge;
            $bt = $charge->balance_transaction ?? null;

            // Convert cents to decimal
            $fee = $bt ? ($bt->fee / 100) : 0;
            $net = $bt ? ($bt->net / 100) : ($paymentIntent->amount / 100);

            // Update Payment
            $payment->update([
                'status' => 'succeeded',
                'stripe_charge_id' => $charge->id ?? null,
                'payment_method' => $paymentIntent->payment_method->type ?? 'card',
                'stripe_processing_fee' => $fee,
                'net_amount' => $net,
                'stripe_response' => json_encode($paymentIntent),
            ]);

            // Update Order
            $order = $payment->order;
            if ($order) {
                $order->update([
                    'is_paid' => true,
                    'status' => 'pending',
                    'stripe_processing_fee' => $fee,
                    'net_amount' => $net,
                ]);

                if ($order->customer) {
                    $order->customer->notify(new OrderStatusNotification($order, 'pending'));
                }
            }
        });

        Log::info('Payment finalized', ['payment_id' => $payment->id, 'fee' => $paymentIntent->latest_charge->balance_transaction->fee ?? 0]);
    }

    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->update(['status' => 'failed']);
            if ($payment->order) {
                $payment->order->update(['status' => 'failed']);
            }
        }
    }
}