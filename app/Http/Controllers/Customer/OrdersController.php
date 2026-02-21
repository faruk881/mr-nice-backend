<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DeliveryRequestRequest;
use App\Http\Requests\Customer\Order\OrderCancelRequest;
use App\Models\DeliveryFeeSetting;
use App\Models\Order;
use App\Models\Refund;
use App\Services\DistanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        $query = auth()->user()->orders()
            ->with('refund')
            ->where('customer_id', auth()->id());
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
    public function store(DeliveryRequestRequest $request, DistanceService $distanceService)
    {       
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
        $booking_date     = $validated['booking_date'];
        
        // Get the distance using lon and lat using google api
        $distanceService = $distanceService->distanceMeasure('haversine',$pickup_lat,$pickup_long,$delivery_lat,$delivery_long);

        // Get the distance
        $distance = $distanceService['distance_km'];
        
        // Get the time            
        $time = $distanceService['minutes'];
        
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
            'customer_id'      => auth()->id(),
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
            'booking_date'     => $booking_date
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
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Get the order
        $order = auth()->user()->orders()
            ->with([
                'courier' => function($query) {
                    // This adds a 'ratings_received_avg_rating' field to the courier object
                    $query->withAvg('ratingsReceived', 'rating'); 
                }, 
                'courier.courierProfile', 
                'payments', 
                'refund'
            ])
            ->find($id);

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

        if($order->customer_id !== auth()->id()) {
            return apiError('You are not authorized to update this order', 403);
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
            $refund = Refund::create([
                'payment_id' => $payment->id,
                'status' => 'requested'
            ]);

            // Update order
            $order->update([
                'status' => 'cancelled',
                'status_reason' => $request->cancel_reason,
            ]);

            return apiSuccess('Order cancelled and refund requested.', $order);

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
