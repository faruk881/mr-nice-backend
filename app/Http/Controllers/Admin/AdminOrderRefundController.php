<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;

class AdminOrderRefundController extends Controller
{
    public function update($orderNumber){

        // Validate order number
        if (!str_starts_with(strtolower($orderNumber), 'lx')) {
            return apiError('Invalid order number format. The order number starts with LX.', 422, [
                'code' => 'INVALID_ORDER_NUMBER'
            ]);
        }
        
        // Get the order
        $order = Order::with('refund')->where('order_number',$orderNumber)->first();

        // Check if order exists
        if(!$order) {
            return apiError('Order not found', 404,['code'=>'ORDER_NOT_FOUND']);
        }

        // Get the refund
        $refund = $order->refund;

        // Check if refund exists
        if(!$refund) {
            return apiError('Refund not found', 404,['code'=>'REFUND_NOT_FOUND']);
        }

        if ($order->is_paid && $order->status == 'cancelled') {
            
            // Get the payment
            $payment = $order->payments()->where('status', 'succeeded')->latest()->first();

            // Check if there is valid payment
            if (!$payment || !$payment->stripe_payment_intent_id) {
                return apiError('No valid payment found to refund.', 400,['code'=>'NO_VALID_PAYMENT_FOUND']);
            }

            $refundAmount = $payment->net_amount;

            // Refund the payment
            Stripe::setApiKey(config('services.stripe.secret'));

            DB::beginTransaction();

            try {
                // Create Stripe refund
                $stripeRefund = StripeRefund::create([
                    'payment_intent' => $payment->stripe_payment_intent_id,
                    'amount' => $refundAmount*100,
                    'reason' => 'requested_by_customer',
                ]);

                // Update payment record
                $payment->update([
                    'status' => 'refunded',
                    'stripe_response' => json_encode($stripeRefund),
                ]);


                $refund->update([
                    'status' => 'succeeded',
                    'amount' => $refundAmount,
                    'stripe_response' => json_encode($stripeRefund),
                ]);

                DB::commit();

                return apiSuccess('Order refunded and cancelled successfully.', $order);
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Stripe Refund Failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'payment_intent' => $payment->stripe_payment_intent_id,
                ]);

                throw $e;
            }
        }

        return apiError('Cannot refund this order', 403,['code'=>'REFUND_NOT_ALLOWED']);

    }

}
