<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\StripeClient;
use Exception;

class StripeWebhookController extends Controller
{
    protected $stripe;

    public function __construct() 
    {
        // Initialize the Stripe Client once for use in all methods
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Main entry point for the Stripe Webhook.
     */
    public function handleWebhook(Request $request) 
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        // 1. Verify the Webhook Signature
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // 2. Route the event to the appropriate handler
        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event->data->object);
                    break;

                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe event type: ' . $event->type);
                    break;
            }
        } catch (Exception $e) {
            Log::error('Stripe Webhook Handling Error: ' . $e->getMessage(), ['event' => $event->id]);
            return response()->json(['error' => 'Webhook handling failed'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handler: checkout.session.completed
     * Fired when a Checkout session is finished successfully.
     */
    protected function handleCheckoutSessionCompleted($session) 
    {
        $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

        if (!$payment) {
            Log::error('Payment not found for Checkout Session', ['session_id' => $session->id]);
            return;
        }

        // Expand payment_method to get card details (brand, last4)
        // Expand latest_charge to get the real 'ch_...' ID
        $paymentIntent = $this->stripe->paymentIntents->retrieve(
            $session->payment_intent,
            ['expand' => ['payment_method', 'latest_charge']]
        );

        $this->processSuccess($payment, $paymentIntent);
    }

    /**
     * Handler: payment_intent.succeeded
     * Fired when a PaymentIntent is successfully authorized and captured.
     */
    protected function handlePaymentIntentSucceeded($paymentIntent) 
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment) {
            // Log as info; this might happen if CheckoutSession handler finished first
            Log::info('Payment record not found for PaymentIntent ID', ['pi' => $paymentIntent->id]);
            return;
        }

        // Ensure we have the expanded payment method details
        if (is_string($paymentIntent->payment_method)) {
            $paymentIntent = $this->stripe->paymentIntents->retrieve(
                $paymentIntent->id,
                ['expand' => ['payment_method', 'latest_charge']]
            );
        }

        $this->processSuccess($payment, $paymentIntent);
    }

    /**
     * Core logic to finalize successful payments.
     * Uses DB Transaction to ensure Payment and Order are updated together.
     */
    protected function processSuccess($payment, $paymentIntent)
    {
        // 1. Idempotency Check: Don't process if already marked as succeeded
        if ($payment->status === 'succeeded') {
            return;
        }

        DB::transaction(function () use ($payment, $paymentIntent) {
            // Extract Card Details safely
            $cardBrand = null;
            $cardLast4 = null;
            
            if ($paymentIntent->payment_method && $paymentIntent->payment_method->type === 'card') {
                $cardBrand = $paymentIntent->payment_method->card->brand;
                $cardLast4 = $paymentIntent->payment_method->card->last4;
            }

            // 2. Update Payment Record
            $payment->update([
                'status'           => 'succeeded',
                'stripe_charge_id' => $paymentIntent->latest_charge->id ?? null,
                'payment_method'   => $paymentIntent->payment_method->type ?? 'card',
                // 'card_type'        => $cardBrand,
                // 'card_last4'       => $cardLast4,
                'stripe_response'  => json_encode($paymentIntent),
            ]);

            // 3. Update Order Record
            if ($payment->order) {
                $payment->order->update([
                    'is_paid' => true,
                    'status'  => 'pending'
                ]);
            }
        });

        Log::info('Payment processed successfully', ['payment_id' => $payment->id]);
    }

    /**
     * Handler: payment_intent.payment_failed
     */
    protected function handlePaymentIntentFailed($paymentIntent) 
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'stripe_response' => json_encode($paymentIntent),
            ]);

            if ($payment->order) {
                $payment->order->update(['status' => 'failed']);
            }
        }
    }
}