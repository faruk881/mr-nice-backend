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

    /**
     * Main Stripe webhook handler
     */
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
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe event', [
                        'type' => $event->type
                    ]);
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
     * checkout.session.completed
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        Log::info('checkout.session.completed', [
            'session_id' => $session->id
        ]);

        $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

        if (!$payment) {
            Log::warning('Payment not found for checkout session', [
                'session_id' => $session->id
            ]);
            return;
        }

        $paymentIntent = $this->stripe->paymentIntents->retrieve(
            $session->payment_intent,
            ['expand' => ['payment_method', 'latest_charge']]
        );

        $this->processSuccess($payment, $paymentIntent);
    }

    /**
     * payment_intent.succeeded
     */
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('payment_intent.succeeded', [
            'pi' => $paymentIntent->id
        ]);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment) {
            Log::info('Payment record not found for payment intent', [
                'pi' => $paymentIntent->id
            ]);
            return;
        }

        if (is_string($paymentIntent->payment_method)) {
            $paymentIntent = $this->stripe->paymentIntents->retrieve(
                $paymentIntent->id,
                ['expand' => ['payment_method', 'latest_charge']]
            );
        }

        $this->processSuccess($payment, $paymentIntent);
    }

    /**
     * Finalize successful payment
     */
    protected function processSuccess($payment, $paymentIntent)
    {
        if ($payment->status === 'succeeded') {
            Log::info('Payment already processed', [
                'payment_id' => $payment->id
            ]);
            return;
        }

        DB::transaction(function () use ($payment, $paymentIntent) {

            $cardBrand = null;
            $cardLast4 = null;

            if (
                $paymentIntent->payment_method &&
                $paymentIntent->payment_method->type === 'card'
            ) {
                $cardBrand = $paymentIntent->payment_method->card->brand;
                $cardLast4 = $paymentIntent->payment_method->card->last4;
            }

            $payment->update([
                'status' => 'succeeded',
                'stripe_charge_id' => $paymentIntent->latest_charge->id ?? null,
                'payment_method' => $paymentIntent->payment_method->type ?? 'card',
                'stripe_response' => json_encode($paymentIntent),
            ]);

            $order = $payment->order;

            if (!$order) {
                Log::warning('Order not found for payment', [
                    'payment_id' => $payment->id
                ]);
                return;
            }

            $order->update([
                'is_paid' => true,
                'status' => 'pending'
            ]);

            if ($order->customer) {
                $order->customer->notify(
                    new OrderStatusNotification($order, 'pending')
                );
            }

        });

        Log::info('Payment processed successfully', [
            'payment_id' => $payment->id
        ]);
    }

    /**
     * payment_intent.payment_failed
     */
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        Log::info('payment_intent.payment_failed', [
            'pi' => $paymentIntent->id
        ]);

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment) {
            Log::warning('Payment not found for failed payment intent', [
                'pi' => $paymentIntent->id
            ]);
            return;
        }

        $payment->update([
            'status' => 'failed',
            'stripe_response' => json_encode($paymentIntent),
        ]);

        if ($payment->order) {
            $payment->order->update([
                'status' => 'failed'
            ]);
        }
    }
}