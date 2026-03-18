<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DeliveryRequestRequest;
use App\Http\Requests\Customer\Order\OrderCancelRequest;
use App\Models\DeliveryFeeSetting;
use App\Models\Order;
use App\Models\Refund;
use App\Services\DistanceService;
use Carbon\Carbon;
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
    public function store(DeliveryRequestRequest $request, DistanceService $distance_service)
    {       
        $validated = $request->validated();

        // Standardize on snake_case for all logic variables
        $booking_date = $validated['booking_date'];
        
        // Calculate departure time
        $departure_time = isset($booking_date)

            // $booking_date only contains date. Sent future time. 
            ? Carbon::parse($booking_date)->setTime(Carbon::now()->hour, Carbon::now()->minute + 1)->toIso8601String()
            : Carbon::now()->addMinute()->toIso8601String();
        
        // Call service (Method remains camelCase per PSR-12)
        $measure = $distance_service->distanceMeasure(
            'google',
            $departure_time,
            $validated['pickup_lat'],
            $validated['pickup_lon'],
            $validated['delivery_lat'],
            $validated['delivery_lon']
        );

        if(!$measure['success']) {
            return apiError($measure['message'], 400, ['code'=>'DISTANCE_MEASURE_FAILED']);
        }

        $distance = $measure['distance_km'];
        $settings = DeliveryFeeSetting::first();

        // Calculation logic
        $base_fare = $settings->base_fare;
        $per_km_price = $settings->per_km_fee;
        $distance_fee = $distance * $per_km_price;

        $package_prices = [
            'small'  => $settings->small_package_fee,
            'medium' => $settings->medium_package_fee,
            'large'  => $settings->large_package_fee,
        ];

        $package_fee = $package_prices[$validated['package_size']];
        $total_fee = max($distance_fee + $package_fee, $base_fare);

        // Use Database Transactions for data integrity
        return DB::transaction(function () use ($validated, $distance, $base_fare, $per_km_price, $package_fee, $total_fee) {
            
            $order = Order::create(array_merge($validated, [
            'customer_id'      => auth()->id(),
            'order_number'     => strtoupper(Str::random(10)), // unique order number
            'pickup_address'   => $validated['pickup_address'],
            'pickup_lat'       => $validated['pickup_lat'],
            'pickup_long'      => $validated['pickup_lon'],
            'pickup_notes'     => $validated['pickup_notes'] ?? null,
            'delivery_address' => $validated['delivery_address'],
            'delivery_lat'     => $validated['delivery_lat'],
            'delivery_long'    => $validated['delivery_lon'],
            'delivery_notes'   => $validated['delivery_notes'] ?? null,
            'package_items'    => $validated['items'],
            'package_size'     => $validated['package_size'],
            'additional_notes' => $validated['additional_notes'] ?? null,
            'distance'         => $distance,
            'base_fare'        => $base_fare,
            'per_km_fee'       => $per_km_price,
            'package_fee'      => $package_fee,
            'total_fee'        => $total_fee,
            'status'           => 'pending_payment',
            'booking_date'     => $validated['booking_date']
            ]));

            // Update with formatted ID
            $order->update([
                'order_number' => 'LX-' . str_pad($order->id, 4, '0', STR_PAD_LEFT)
            ]);

            return apiSuccess('Delivery request created successfully!', [
                'estimates' => [
                    'distance'    => $order->distance,
                    'per_km_fee'  => $order->per_km_fee,
                    'package_fee' => $package_fee,
                    'total_fee'   => $order->total_fee,
                ],
                'order' => $order,
            ], 201);
        });
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
            return apiError('Order not found', 404, ['code'=>'ORDER_NOT_FOUND']);
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
            return apiError('Order not found', 404, ['code'=>'ORDER_NOT_FOUND']);
        }

        if($order->customer_id !== auth()->id()) {
            return apiError('You are not authorized to update this order', 403, ['code'=>'NOT_AUTHORIZED']);
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
        return apiError('Cannot cancel this order', 403, ['code'=>'NOT_ALLOWED']);
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return apiSuccess('destroy');
    }
}
