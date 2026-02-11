<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\CalculatePriceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderPriceController extends Controller
{
    public function estimate(CalculatePriceRequest $request)
    {
        // ... validation logic ...
        try {
            $apiKey = config('services.google_maps.key'); // Set this in config/services.php
            $origin = $request->pickup;      // "Mohakhali, Dhaka"
            $destination = $request->delivery; // "Mohammadpur, Dhaka"

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
        } catch (\Exception $e) {
            return apiError($e->getMessage());
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
