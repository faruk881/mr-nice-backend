<?php

namespace App\Services;


use Illuminate\Support\Facades\Http;


class DistanceService
{
    /**
     * Measure distance
     */
    public function distanceMeasure(string $measureMethod, float $pickup_lat, float $pickup_lon, float $delivery_lat, float $delivery_lon)
    {
        if ($measureMethod === 'haversine') {
            // Earth's radius in kilometers (use 3958.8 for miles)
            $earthRadius = 6371;

            // Convert degrees to radians
            $latFrom = deg2rad($pickup_lat);
            $lonFrom = deg2rad($pickup_lon);
            $latTo = deg2rad($delivery_lat);
            $lonTo = deg2rad($delivery_lon);

            // Differences in coordinates
            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            // The Haversine Formula
            $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

            $distanceKm = $angle * $earthRadius;

            $estimatedRoadDist = $distanceKm * 1.3;

            /**
             * 2. Average Speed (km/h)
             * City driving is usually 30-40 km/h. 
             * Highway driving is usually 80-100 km/h.
             */
            $averageSpeed = 40; 

            // Time = Distance / Speed
            $timeHours = $estimatedRoadDist / $averageSpeed;
            
            // Convert to minutes
            $timeMinutes = round($timeHours * 60);

            return [
                'success' => true,
                'distance_km' => round($estimatedRoadDist, 2),
                'minutes' => $timeMinutes
            ];

        }

        if ($measureMethod === 'google') {
            $apiKey = config('services.google_maps.key'); // Best practice: use config/env
            $origins = $pickup_lat . ',' . $pickup_lon;
            $destinations = $delivery_lat . ',' . $delivery_lon;
            $units = 'imperial'; // or 'metric' based on your preference

            // Distance Matrix response
            // $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            //     'destinations' => $destinations,
            //     'origins'      => $origins,
            //     'units'        => $units,
            //     'key'          => $apiKey,
            // ]);

            // Routes API response
            $response = Http::withHeaders([
                'Content-Type'    => 'application/json',
                'X-Goog-Api-Key'   => $apiKey,
                // Note: status and condition are highly recommended for debugging
                'X-Goog-FieldMask' => 'originIndex,destinationIndex,distanceMeters,duration,status,condition',
            ])->post('https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix', [
                'origins' => [
                    ['waypoint' => ['location' => ['latLng' => ['latitude' => (float)$pickup_lat, 'longitude' => (float)$pickup_lon]]]]
                ],
                'destinations' => [
                    ['waypoint' => ['location' => ['latLng' => ['latitude' => (float)$delivery_lat, 'longitude' => (float)$delivery_lon]]]]
                ],
                'travelMode'        => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE',
            ]);

            return [
                'success' => true,
                'distance_km' => null,
                'minutes' => null,
                'response' => $response->json()
            ];
            // if ($response->successful()) {
            //     $data = $response->json();
                
            //     // Access specific data:
            //     $distance = $data['rows'][0]['elements'][0]['distance']['text'];
            //     $duration = $data['rows'][0]['elements'][0]['duration']['text'];

            //     return response()->json([
            //         'distance' => $distance,
            //         'duration' => $duration
            //     ]);
            // }

            return [
                'success' => false,
                'message' => 'Failed to calculate distance using Google API',
                'details' => $response->json()
            ];
        }
    }
}
