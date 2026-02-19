<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Customer as StripeCustomer;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function store(PaymentRequest $request, $orderId)
    {
        // Fetch the order with the related user
        $order = Order::with('user')->findOrFail($orderId);
        $user = $order->user;
        
        // Check if order is belongs to that user.
        if($order->customer_id !== auth()->id()) {
            return apiError('You are not authorized to update this order', 403);
        }

        // Prevent payment if already paid
        if ($order->is_paid) {
            return apiError('Order already paid.', 400);
        }

        // Set Stripe secret key
        Stripe::setApiKey(config('services.stripe.secret'));

        // 1. Create or retrieve Stripe customer
        if (!$user->stripe_customer_id) {
            $stripeCustomer = StripeCustomer::create([
                'email' => $user->email,
                'name'  => $user->name,
            ]);
            $user->stripe_customer_id = $stripeCustomer->id;
            $user->save();
        } else {
            $stripeCustomer = StripeCustomer::retrieve($user->stripe_customer_id);
        }

        $paymentMode = $request->payment_mode; // 'link' or 'intent'

        // 2. Handle Checkout Session (Payment Link)
        if ($paymentMode === 'link') {
            $payment = $order->payments()
                ->whereNotNull('stripe_checkout_session_id')
                ->where('status', 'pending')
                ->latest()
                ->first();

            $createNewSession = true;

            if ($payment) {
                try {
                    $session = StripeSession::retrieve($payment->stripe_checkout_session_id);
                    if ($session->status === 'open' && $session->payment_status === 'unpaid') {
                        $createNewSession = false;
                    }
                } catch (\Exception $e) {
                    $createNewSession = true;
                }
            }

            if ($createNewSession) {
                $session = StripeSession::create([
                    'customer' => $stripeCustomer->id,
                    'payment_method_types' => ['card', 'twint'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'chf',
                            'product_data' => ['name' => "Order #{$order->id}"],
                            'unit_amount' => $order->total_fee * 100,
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'metadata' => [
                        'order_id' => $order->id,
                        'customer_id' => $user->id,
                    ],
                    'success_url' => config('app.frontend_url'),
                    'cancel_url' => config('app.frontend_url'),
                ]);
            }

            $payment = $this->savePaymentRecord($payment, $order, $stripeCustomer->id, [
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id'  => $session->payment_intent,
                'status' => 'pending',
                'amount' => $order->total_fee,
                'currency' => 'chf',
            ]);

            return apiSuccess('Payment link created successfully.', [
                'type' => 'link',
                'payment_link' => $session->url
            ]);
        }

        // 3. Handle Payment Intent
        if ($paymentMode === 'intent') {
            $payment = $order->payments()
                ->whereNotNull('stripe_payment_intent_id')
                ->where('status', 'pending')
                ->latest()
                ->first();

            $createNewIntent = true;

            if ($payment) {
                try {
                    $paymentIntent = PaymentIntent::retrieve($payment->stripe_payment_intent_id);
                    if (in_array($paymentIntent->status, ['requires_payment_method', 'requires_confirmation'])) {
                        $createNewIntent = false;
                    }
                } catch (\Exception $e) {
                    $createNewIntent = true;
                }
            }

            if ($createNewIntent) {
                $paymentIntent = PaymentIntent::create([
                    'amount' => $order->total_fee * 100,
                    'currency' => 'chf',
                    'customer' => $stripeCustomer->id,
                    'payment_method_types' => ['card', 'twint'],
                    'metadata' => [
                        'order_id' => $order->id,
                        'customer_id' => $user->id,
                    ],
                ]);
            }

            $payment = $this->savePaymentRecord($payment, $order, $stripeCustomer->id, [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'status' => 'pending',
                'amount' => $order->total_fee,
                'currency' => 'chf',
            ]);

            // Fetch saved cards
            $savedCards = PaymentMethod::all([
                'customer' => $stripeCustomer->id,
                'type' => 'card',
            ]);
            

            return apiSuccess('Payment intent created successfully.', [
                'type' => 'intent',
                'publishable_key' => config('services.stripe.publishable'),
                'client_secret' => $paymentIntent->client_secret,
                'saved_cards' => $savedCards->data, 
            ]);
        }

        return apiError('Invalid payment mode.', 400);
    }

    private function savePaymentRecord($payment, $order, $stripeCustomerId, array $data)
    {
        if ($payment) {
            $payment->update($data);
        } else {
            $payment = Payment::create(array_merge($data, [
                'order_id' => $order->id,
                'stripe_customer_id' => $stripeCustomerId,
            ]));
        }
        return $payment;
    }
}