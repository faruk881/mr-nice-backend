<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DeliveryRequestRequest;
use App\Http\Requests\Orders\CalculatePriceRequest;
use App\Models\DeliveryFeeSetting;
use App\Services\DistanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderPriceController extends Controller
{
    public function estimate(DeliveryRequestRequest $request, DistanceService $distanceService)
    {
        $pickup_lat = $request->pickup_lat;
        $pickup_lon = $request->pickup_lon;
        $delivery_lat = $request->delivery_lat;
        $delivery_lon = $request->delivery_lon;

        $distanceService = $distanceService->distanceMeasure('haversine',$pickup_lat,$pickup_lon,$delivery_lat,$delivery_lon);
        // return $distanceService;

        if(!$distanceService['success']){
            return apiError('Failed to calculate distance', 500,$distanceService);
        }

        $distance = $distanceService['distance_km'];
        $time = $distanceService['minutes'];


        // Get the pricing settings
        $prices = DeliveryFeeSetting::first();

        // Check if price exists
        if (!$prices) {
            return apiError('Delivery pricing settings not found', 404);
        }

        // google api key needed later
        $apiKey = config('services.google_maps.key');
        
        // Get requests
        $origin = $request->pickup; 
        $destination = $request->delivery; 
        
        $items = $request->items;
        

        // Get Delivery Settings
        $baseFare = (string) $prices->base_fare;
        $pricePerKm =(string) $prices->per_km_fee;
        $packagePrices = [
            'small' => (string) $prices->small_package_fee,
            'medium' => (string) $prices->medium_package_fee,
            'large' => (string) $prices->large_package_fee,
        ];

        // Get package size
        $packageSize = $request->package_size;
        $packagePrice = $packagePrices[$packageSize];

        // Calculate Total Price
        $totalPrice = round(max(($distance*$pricePerKm)+$packagePrice,$baseFare),2);

        // Prepare response data
        $data = [
            'items' => $items,
            'distance' => $distance,
            'base_fare' => $baseFare,
            'per_km_fee' => $pricePerKm,
            'package_size' => $packageSize,
            'package_fee' => $packagePrice,
            'total_fee' => $totalPrice,
            'est_time_minutes' => $time,
        ];

        // Return with data
        return apiSuccess('Delivery fee calculated successfully', $data);
    }

}
