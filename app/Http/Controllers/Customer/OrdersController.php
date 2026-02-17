<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DeliveryRequestRequest;
use App\Http\Requests\Customer\Order\OrderCancelRequest;
use App\Models\DeliveryFeeSetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Refund;
use Stripe\Stripe;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $status = $request->query('status', 'all');

        $query = Order::query();

        switch ($status) {
            case 'active':
                $query->where('status', 'pending');
                break;
            case 'completed':
                $query->where('status', 'delivered');
                break;
            case 'cancelled':
                $query->where('status', 'cancelled');
                break;
            case 'all':
            default:
                // no filtering
                break;
        }

        $orders = $query->paginate($perPage);

        return apiSuccess('Orders loaded', $orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DeliveryRequestRequest $request)
    {
        try {
            
            // Validate request
            $validated = $request->validated();

            // Get the request
            $pickup_address   = $validated['pickup_address'];
            $pickup_lat       = $validated['pickup_lat'];
            $pickup_long      = $validated['pickup_lon'];
            $pickup_notes     = $validated['pickup_notes'] ?? null;
            $delivery_address = $validated['delivery_address'];
            $delivery_lat     = $validated['delivery_lat'];
            $delivery_long    = $validated['delivery_lon'];
            $delivery_notes   = $validated['delivery_notes'] ?? null;
            $package_items    = $validated['items'];
            $package_size     = $validated['package_size'];
            $additional_notes = $validated['additional_notes'] ?? null;
            
            // Get the distance using lon and lat using google api
            $distance = 5;
            
            // Get Delivery pricing settings
            $prices = DeliveryFeeSetting::first();

            $baseFare = $prices->base_fare;
            $pricePerKm = $prices->per_km_fee;
            $distanceFee = $distance*$pricePerKm;

            $packagePrices = [
                'small' => (string) $prices->small_package_fee,
                'medium' => (string) $prices->medium_package_fee,
                'large' => (string) $prices->large_package_fee,
            ];

            // Get package size
            $packageSize = $request->package_size;
            $packagePrice = $packagePrices[$packageSize];

            $totalFee = max($distanceFee+$packagePrice,$baseFare);

            // Create order
            $order = Order::create([
                'user_id'          => auth()->id(),
                'order_number'     => strtoupper(Str::random(10)), // unique order number
                'pickup_address'   => $pickup_address,
                'pickup_lat'       => $pickup_lat,
                'pickup_long'      => $pickup_long,
                'pickup_notes'     => $pickup_notes,
                'delivery_address' => $delivery_address,
                'delivery_lat'     => $delivery_lat,
                'delivery_long'    => $delivery_long,
                'delivery_notes'   => $delivery_notes,
                'package_items'    => $package_items,
                'package_size'     => $package_size,
                'additional_notes' => $additional_notes,
                'distance'         => $distance,
                'base_fare'        => $baseFare,
                'per_km_fee'       => $pricePerKm,
                'package_fee'      => $packagePrice,
                'total_fee'        => $totalFee,
                'status'           => 'pending_payment',
                'booking_date'     => now(), // can be customized
            ]);

            // Generate and save order id
            $order->order_number = 'LX-'.str_pad($order->id, 4, '0', STR_PAD_LEFT);
            $order->save();

            // Return response

            $estimates = [
                'distance' => $order->distance,
                'per_km_fee' => $order->per_km_fee,
                'package_fee' => $packagePrice,
                'total_fee' => $order->total_fee,
                
            ];

            $data = [
                'estimates' => $estimates,
                'order' => $order,

            ];

            return apiSuccess('Delivery request created successfully!', $data, 201);
            
        } catch (\Throwable $e) {
            return apiError($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Get the order
        $order = Order::with(['courier','courier.courierProfile', 'payments'])->find($id);

        // Check if order exists
        if (!$order) {
            return apiError('Order not found', 404);
        }
        return apiSuccess('Order loaded',$order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OrderCancelRequest $request, string $id)
    {
        // Get the order
        $order = Order::find($id);

        // Check if order exists
        if(!$order) {
            return apiError('Order not found', 404);
        }

        // Cancel if order status is only pending_payment and pending
        if($order->status == 'pending_payment') {
            $order->status = 'cancelled';
            $order->status_reason = $request->cancel_reason;
            $order->save();
            return apiSuccess('Order Cancelled.',$order); 
        }
        
        
        if($order->is_paid && $order->status == 'pending') {
            
            // Get the payment
            $payment = $order->payments()->where('status', 'succeeded')->latest()->first();

            if (!$payment || !$payment->stripe_payment_intent_id) {
                return apiError('No valid payment found to refund.', 400);
            }

            // Refund the payment
            Stripe::setApiKey(config('services.stripe.secret'));

            DB::beginTransaction();

            try {
                // Create Stripe refund
                $refund = Refund::create([
                    'payment_intent' => $payment->stripe_payment_intent_id,
                ]);

                // Update payment record
                $payment->update([
                    'status' => 'refunded',
                    'stripe_response' => json_encode($refund),
                ]);

                // Update order
                $order->update([
                    'status' => 'cancelled',
                    'status_reason' => $request->cancel_reason,
                    'is_paid' => false,
                ]);

                DB::commit();

                return apiSuccess('Order refunded and cancelled successfully.', $order);
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Stripe Refund Failed: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'payment_intent' => $payment->stripe_payment_intent_id,
                ]);

                return apiError('Refund failed. Please try again.', 500);
            }
        }

        // Otherwise cannot cancel the order
        return apiError('Cannot cancel this order', 403);
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return apiSuccess('destroy');
    }
}
