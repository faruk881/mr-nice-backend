<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Customer as StripeCustomer;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    /**
     * Store or generate a payment for an order.
     *
     * @param PaymentRequest $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PaymentRequest $request, $orderId)
    {
        // Fetch the order with the related user
        $order = Order::with('user')->findOrFail($orderId);
        $user = $order->user;

        // Prevent payment if already paid
        if ($order->is_paid) {
            return apiError('Order already paid.', 400);
        }

        // Set Stripe secret key
        Stripe::setApiKey(config('services.stripe.secret'));

        // -------------------------------
        // 1️⃣ Create or retrieve Stripe customer
        // -------------------------------
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

        // -------------------------------
        // 2️⃣ Handle Checkout Session (Payment Link)
        // -------------------------------
        if ($paymentMode === 'link') {

            // Find existing pending payment with a checkout session
            $payment = $order->payments()
                ->whereNotNull('stripe_checkout_session_id')
                ->where('status', 'pending')
                ->latest()
                ->first();

            $createNewSession = true;

            if ($payment) {
                try {
                    $session = StripeSession::retrieve($payment->stripe_checkout_session_id);

                    // If session is still open & unpaid, reuse it
                    if ($session->status === 'open' && $session->payment_status === 'unpaid') {
                        $createNewSession = false;
                    }
                } catch (\Exception $e) {
                    // Session invalid or not found → create new
                    $createNewSession = true;
                }
            }

            // Create new session if needed
            if ($createNewSession) {
                $session = StripeSession::create([
                    'customer' => $stripeCustomer->id,
                    'payment_method_types' => ['card', 'twint'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'chf',
                            'product_data' => [
                                'name' => "Order #{$order->id}",
                            ],
                            'unit_amount' => $order->total_fee * 100, // in cents
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'metadata' => [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                    ],
                    'success_url' => route('payment.success', ['orderId' => $order->id]),
                    'cancel_url' => route('payment.cancel', ['orderId' => $order->id]),
                ]);
            }

            // Store or update payment record
            $payment = $this->savePaymentRecord($payment, $order, $stripeCustomer->id, [
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id'  => $session->payment_intent,
                'status' => 'pending',
                'amount' => $order->total_fee,
                'currency' => 'chf',
            ]);

            // Return payment link
            return apiSuccess('Payment link created successfully.', [
                'type' => 'link',
                'payment_link' => $session->url
            ]);
        }

        // -------------------------------
        // 3️⃣ Handle Payment Intent
        // -------------------------------
        if ($paymentMode === 'intent') {

            // Find existing pending payment intent
            $payment = $order->payments()
                ->whereNotNull('stripe_payment_intent_id')
                ->where('status', 'pending')
                ->latest()
                ->first();

            $createNewIntent = true;

            if ($payment) {
                try {
                    $paymentIntent = PaymentIntent::retrieve($payment->stripe_payment_intent_id);

                    // Reuse intent if still requires payment or confirmation
                    if (in_array($paymentIntent->status, ['requires_payment_method', 'requires_confirmation'])) {
                        $createNewIntent = false;
                    }
                } catch (\Exception $e) {
                    $createNewIntent = true; // Invalid or missing intent → create new
                }
            }

            // Create new Payment Intent if needed
            if ($createNewIntent) {
                $paymentIntent = PaymentIntent::create([
                    'amount' => $order->total_fee * 100,
                    'currency' => 'chf',
                    'customer' => $stripeCustomer->id,
                    'payment_method_types' => ['card', 'twint'],
                    'metadata' => [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                    ],
                ]);
            }

            // Store or update payment record
            $payment = $this->savePaymentRecord($payment, $order, $stripeCustomer->id, [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'status' => 'pending',
                'amount' => $order->total_fee,
                'currency' => 'chf',
            ]);

            // Return client secret for frontend to confirm payment
            return apiSuccess('Payment intent created successfully.', [
                'type' => 'intent',
                'publishable_key' => config('services.stripe.publishable'),
                'client_secret' => $paymentIntent->client_secret
            ]);
        }

        return apiError('Invalid payment mode.', 400);
    }

    /**
     * Save or update payment record in the database.
     *
     * @param Payment|null $payment
     * @param Order $order
     * @param string $stripeCustomerId
     * @param array $data
     * @return Payment
     */
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
