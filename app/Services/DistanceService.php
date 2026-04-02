<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Service to calculate distances and travel times between two geographic points.
 */
class DistanceService
{
    // Constants for Haversine calculations
    private const EARTH_RADIUS_KM = 6371;
    private const ROAD_ADJUSTMENT_FACTOR = 1.3; // Approximation for road bends
    private const AVG_CITY_SPEED_KMH = 40;

    /**
     * Measures distance and duration based on the chosen method.
     *
     * @param string $measureMethod 'haversine' (math) or 'google' (API)
     * @param string $departureTime ISO 8601 timestamp (e.g., "2023-10-15T10:00:00Z")
     * @param float $pickupLat Latitude of starting point
     * @param float $pickupLon Longitude of starting point
     * @param float $deliveryLat Latitude of destination
     * @param float $deliveryLon Longitude of destination
     * @return array Contains success status, distanceKm, durationMinutes, and optional message
     */
    public function distanceMeasure(
        string $measure_method,
        string $departure_time,
        float $pickup_lat,
        float $pickup_lon,
        float $delivery_lat,
        float $delivery_lon
    ): array {
        if ($measure_method === 'haversine') {
            return $this->calculateHaversine($pickup_lat, $pickup_lon, $delivery_lat, $delivery_lon);
        }

        if ($measure_method === 'google') {
            return $this->calculateGoogleRoutes($departure_time, $pickup_lat, $pickup_lon, $delivery_lat, $delivery_lon);
        }

        return [
            'success' => false,
            'message' => 'Unsupported measurement method provided.',
        ];
    }

    /**
     * Calculates "As the crow flies" distance using the Haversine formula.
     * 
     */
    private function calculateHaversine(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        // Convert degrees to radians
        $lat_from = deg2rad($lat1);
        $lon_from = deg2rad($lon1);
        $lat_to = deg2rad($lat2);
        $lon_to = deg2rad($lon2);

        $lat_delta = $lat_to - $lat_from;
        $lon_delta = $lon_to - $lon_from;

        // Haversine formula calculation
        $angle = 2 * asin(sqrt(pow(sin($lat_delta / 2), 2) +
            cos($lat_from) * cos($lat_to) * pow(sin($lon_delta / 2), 2)));

        $direct_dist = $angle * self::EARTH_RADIUS_KM;
        
        // Adjust for road distance (multiplying by 1.3)
        $estimated_road_dist = $direct_dist * self::ROAD_ADJUSTMENT_FACTOR;
        $time_hours = $estimated_road_dist / self::AVG_CITY_SPEED_KMH;

        return [
            'success' => true,
            'distance_km' => round($estimated_road_dist, 2),
            'duration_minutes' => (int) round($time_hours  * 60),
            'method' => 'haversine'
        ];
    }

    /**
     * Fetches real-world road distance and traffic-aware duration via Google Routes API v2.
     */
    private function calculateGoogleRoutes(string $time, float $pickup_lat, float $pickup_lon, float $delivery_lat, float $delivery_lon): array
    {
        $api_key = config('services.google_maps.key');

        $allowed_cities = ['Sion', 'Sierre', 'Martigny', 'Monthey'];

        // 1. Validate Pickup City
        $pickup_city = $this->getCityFromCoordinates($pickup_lat, $pickup_lon, $api_key);
        if (!in_array($pickup_city, $allowed_cities)) {
            return ['success' => false, 'message' => "Pickup city ($pickup_city) is not allowed."];
        }

        // 2. Validate Delivery City
        $delivery_city = $this->getCityFromCoordinates($delivery_lat, $delivery_lon, $api_key);
        if (!in_array($delivery_city, $allowed_cities)) {
            return ['success' => false, 'message' => "Delivery city ($delivery_city) is not allowed."];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $api_key,
                'X-Goog-FieldMask' => 'distanceMeters,duration,status',
            ])->post('https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix', [
                'origins' => [['waypoint' => ['location' => ['latLng' => ['latitude' => $pickup_lat, 'longitude' => $pickup_lon]]]]],
                'destinations' => [['waypoint' => ['location' => ['latLng' => ['latitude' => $delivery_lat, 'longitude' => $delivery_lon]]]]],
                'travelMode' => 'DRIVE',
                'departureTime' => $time,
                'routingPreference' => 'TRAFFIC_AWARE_OPTIMAL',
            ]);

            if (!$response->successful()) {
                Log::error("Google API HTTP Error", ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'message' => $response->body()];
            }

            $data = $response->json();
            $result = $data[0] ?? null;

            return [
                'success' => true,
                'distance_km' => round($result['distanceMeters'] / 1000, 2),
                'duration_minutes' => (int) ceil(((float) rtrim($result['duration'], 's')) / 60),
                'response' => $result,
            ];

        } catch (Throwable $e) {
            Log::error("DistanceService Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred.'];
        }
    }
    
    /**
     * Helper to get City Name via Reverse Geocoding
     */
    private function getCityFromCoordinates(float $lat, float $lon, string $key): ?string
    {
        $response = Http::get("https://maps.googleapis.com/maps/api/geocode/json", [
            'latlng' => "$lat,$lon",
            'key' => $key,
            'result_type' => 'locality' // Focuses results on the city/town level
        ]);

        if ($response->successful()) {
            $results = $response->json('results');
            if (!empty($results)) {
                // Extracts the "long_name" from the address components
                return $results[0]['address_components'][0]['long_name'] ?? null;
            }
        }
        Log::error("Google API HTTP Error", ['status' => $response->status(), 'body' => $response->body()]);

        return null;
    }
}