<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DeliveryRequestRequest;
use App\Models\DeliveryFeeSetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeliveryRequestController extends Controller
{
    public function store(DeliveryRequestRequest $request) {
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

            $serviceFee = $prices->service_fee;
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

            $totalFee = max($distanceFee+$packagePrice+$serviceFee,$baseFare);

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
                'service_fee'      => $serviceFee,
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
                'service_fee' => $order->service_fee,
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
}
