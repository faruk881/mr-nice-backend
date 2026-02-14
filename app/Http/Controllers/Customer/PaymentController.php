<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function store($orderId) {
        $order = Order::findOrFail($orderId);

        if ($order->is_paid) {
            return response()->json([
                'message' => 'Order already paid.'
            ], 400);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
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
            ],
            'success_url' => route('payment.success', ['orderId' => $order->id]),
            'cancel_url' => route('payment.cancel', ['orderId' => $order->id]),
        ]);

        // Store payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'stripe_checkout_session_id' => $session->id,
            'payment_link' => $session->url,
            'amount' => $order->total_fee,
            'currency' => 'chf',
            'status' => 'pending',
        ]);

        return response()->json([
            'payment_link' => $session->url
        ]);
        
    }
}
