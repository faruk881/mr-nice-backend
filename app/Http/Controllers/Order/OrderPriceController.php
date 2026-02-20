<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\CalculatePriceRequest;
use App\Models\DeliveryFeeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderPriceController extends Controller
{
    public function estimate(CalculatePriceRequest $request)
    {
        // ... validation logic ...
        try {

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

            // Get distance
            $distance = '2';

            // Get package size
            $packageSize = $request->package_size;
            $packagePrice = $packagePrices[$packageSize];

            // Calculate Total Price
            $totalPrice = max(($distance*$pricePerKm)+$packagePrice,$baseFare);

            // Prepare response data
            $data = [
                'items' => $items,
                'base_fare' => $baseFare,
                'per_km_price' => $pricePerKm,
                'total_distance' => $distance,
                'package_size' => $packageSize,
                'package_fee' => $packagePrice,
                'total_fee' => $totalPrice,
            ];

            // Return with data
            return apiSuccess('Delivery fee calculated successfully', $data);

         

            // $response = Http::withHeaders([
            //     'Content-Type' => 'application/json',
            //     'X-Goog-Api-Key' => 'AIzaSyAaGMD65D3ok2rlVq32AfS3aDxUkO2DXWQ',
            //     'X-Goog-FieldMask' => 'routes.duration,routes.distanceMeters',
            // ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
            //     'origin' => [
            //         'address' => '1600 Amphitheatre Parkway, Mountain View, CA',
            //     ],
            //     'destination' => [
            //         'address' => '450 Serra Mall, Stanford, CA',
            //     ],
            //     'travelMode' => 'DRIVE',
            //     'routingPreference' => 'TRAFFIC_AWARE',
            // ]);

            return apiSuccess('The fee is: ',$request->validated());
        } catch (\Throwable $e) {
            return apiError($e->getMessage(), $e->getCode(),['debug_message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }


        // if ($response->successful()) {
        //     $data = $response->json();
            
        //     // Check if Google actually found a route
        //     if ($data['rows'][0]['elements'][0]['status'] === 'OK') {
        //         $distanceInMeters = $data['rows'][0]['elements'][0]['distance']['value'];
        //         $distanceText = $data['rows'][0]['elements'][0]['distance']['text'];
                
        //         $km = $distanceInMeters / 1000;
        //         // $price = $this->calculateFinalPrice($km, $request->package_size);
        //         $data = [
        //             'km' => $km,
        //             'raw_data' => $data,
        //         ];

        //         return apiSuccess('The data retrived successfully',$data);
        //     }
        // }
    }

}
