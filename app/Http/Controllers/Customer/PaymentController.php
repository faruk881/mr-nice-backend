<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function store(Request $request, $orderId) {
        $order = Order::with('user')->findOrFail($orderId);
        $user = $order->user;

        if ($order->is_paid) {
            return response()->json([
                'message' => 'Order already paid.'
            ], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        // --- Create Stripe Customer if not exists ---
        if (!$user->stripe_customer_id) {
            $stripeCustomer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
            ]);

            $user->stripe_customer_id = $stripeCustomer->id;
            $user->save();
        } else {
            $stripeCustomer = Customer::retrieve($user->stripe_customer_id);
        }

        $usePaymentLink = $request->input('use_payment_link', true);

        if ($usePaymentLink) {
            // --- Checkout Session / Payment Link ---
            $session = Session::create([
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card', 'twint'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'chf',
                        'product_data' => [
                            'name' => "Order #{$order->id}",
                        ],
                        'unit_amount' => $order->total_fee * 100,
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

            // Store payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent,
                // 'payment_link' => $session->url,
                'amount' => $order->total_fee,
                'currency' => 'chf',
                'status' => 'pending',
                'stripe_customer_id' => $stripeCustomer->id,
            ]);

            return response()->json([
                'payment_link' => $session->url
            ]);

        }
        // else {
        //     // --- PaymentIntent (frontend flow) ---
        //     $paymentIntent = PaymentIntent::create([
        //         'amount' => $order->total_fee * 100,
        //         'currency' => 'chf',
        //         'customer' => $stripeCustomer->id,
        //         'automatic_payment_methods' => [
        //             'enabled' => true,
        //         ],
        //         'metadata' => [
        //             'order_id' => $order->id,
        //             'user_id' => $user->id,
        //         ]
        //     ]);

        //     // Store payment record
        //     $payment = Payment::create([
        //         'order_id' => $order->id,
        //         'stripe_payment_intent_id' => $paymentIntent->id,
        //         'amount' => $order->total_fee,
        //         'currency' => 'chf',
        //         'status' => 'pending',
        //         'stripe_customer_id' => $stripeCustomer->id,
        //     ]);

        //     return response()->json([
        //         'client_secret' => $paymentIntent->client_secret
        //     ]);
        // }
        
    }
}
