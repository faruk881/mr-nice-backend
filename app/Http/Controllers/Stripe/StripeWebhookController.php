<?php

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\Webhook;
use Exception;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Initialize Stripe
        Stripe::setApiKey(config('services.stripe.secret'));

        // Parse the request body
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        // Construct the event
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Invalid Payload: '.$e->getMessage(), ['payload' => $payload]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook Invalid Signature: '.$e->getMessage(), ['payload' => $payload]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (Exception $e) {
            Log::error('Stripe Webhook Exception: '.$e->getMessage(), ['payload' => $payload]);
            return response()->json(['error' => 'Webhook error'], 500);
        }

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentSucceeded($paymentIntent);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentFailed($paymentIntent);
                    break;

                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $this->handleCheckoutSessionCompleted($session);
                    break;

                default:
                    Log::info('Unhandled Stripe event type: '.$event->type, ['event' => $event]);
                    break;
            }
        } catch (Exception $e) {
            Log::error('Stripe Webhook Handling Error: '.$e->getMessage(), ['event' => $event]);
            return response()->json(['error' => 'Webhook handling failed'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment) {
            Log::error('Payment not found for PaymentIntent', ['payment_intent_id' => $paymentIntent->id]);
            return;
        }

        if ($payment->status != 'succeeded') {
            try {
                $payment->update([
                    'status' => 'succeeded',
                    'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                    'stripe_response' => json_encode($paymentIntent),
                ]);

                $payment->order->update([
                    'is_paid' => true,
                    'status' => 'pending'
                ]);
            } catch (Exception $e) {
                Log::error('Error updating payment/order for PaymentIntent: '.$e->getMessage(), ['payment_intent_id' => $paymentIntent->id]);
            }
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent)
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment) {
            Log::error('Payment not found for failed PaymentIntent', ['payment_intent_id' => $paymentIntent->id]);
            return;
        }

        try {
            $payment->update([
                'status' => 'failed',
                'stripe_response' => json_encode($paymentIntent),
            ]);
        } catch (Exception $e) {
            Log::error('Error updating failed PaymentIntent: '.$e->getMessage(), ['payment_intent_id' => $paymentIntent->id]);
        }
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

        if (!$payment) {
            Log::error('Payment not found for Checkout Session', ['session_id' => $session->id]);
            return;
        }

        if ($payment->status != 'succeeded') {
            try {
                $payment->update([
                    'status' => 'succeeded',
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'stripe_charge_id' => $session->payment_status === 'paid' ? $session->payment_intent : null,
                    'stripe_response' => json_encode($session),
                ]);

                $payment->order->update([
                    'is_paid' => true,
                    'status' => 'pending'
                ]);
            } catch (Exception $e) {
                Log::error('Error updating payment/order for Checkout Session: '.$e->getMessage(), ['session_id' => $session->id]);
            }
        }
    }
}
