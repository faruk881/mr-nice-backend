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
        string $measureMethod,
        string $departureTime,
        float $pickupLat,
        float $pickupLon,
        float $deliveryLat,
        float $deliveryLon
    ): array {
        if ($measureMethod === 'haversine') {
            return $this->calculateHaversine($pickupLat, $pickupLon, $deliveryLat, $deliveryLon);
        }

        if ($measureMethod === 'google') {
            return $this->calculateGoogleRoutes($departureTime, $pickupLat, $pickupLon, $deliveryLat, $deliveryLon);
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
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        // Haversine formula calculation
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $directDist = $angle * self::EARTH_RADIUS_KM;
        
        // Adjust for road distance (multiplying by 1.3)
        $estimatedRoadDist = $directDist * self::ROAD_ADJUSTMENT_FACTOR;
        $timeHours = $estimatedRoadDist / self::AVG_CITY_SPEED_KMH;

        return [
            'success' => true,
            'distanceKm' => round($estimatedRoadDist, 2),
            'durationMinutes' => (int) round($timeHours * 60),
            'method' => 'haversine'
        ];
    }

    /**
     * Fetches real-world road distance and traffic-aware duration via Google Routes API v2.
     */
    private function calculateGoogleRoutes(string $time, float $pLat, float $pLon, float $dLat, float $dLon): array
    {
        $apiKey = config('services.google_maps.key');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'distanceMeters,duration,status',
            ])->post('https://routes.googleapis.com/distanceMatrix/v2:computeRouteMatrix', [
                'origins' => [['waypoint' => ['location' => ['latLng' => ['latitude' => $pLat, 'longitude' => $pLon]]]]],
                'destinations' => [['waypoint' => ['location' => ['latLng' => ['latitude' => $dLat, 'longitude' => $dLon]]]]],
                'travelMode' => 'DRIVE',
                'departureTime' => $time,
                'routingPreference' => 'TRAFFIC_AWARE_OPTIMAL',
            ]);

            if (!$response->successful()) {
                Log::error("Google API HTTP Error", ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'message' => 'Distance API unavailable.'];
            }

            $data = $response->json();
            $result = $data[0] ?? null;

            return [
                'success' => true,
                'distanceKm' => round($result['distanceMeters'] / 1000, 2),
                'durationMinutes' => (int) ceil(((float) rtrim($result['duration'], 's')) / 60),
                'response' => $result,
            ];

        } catch (Throwable $e) {
            Log::error("DistanceService Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred.'];
        }
    }
}