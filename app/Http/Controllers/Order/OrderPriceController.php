<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DeliveryRequestRequest;
use App\Models\DeliveryFeeSetting;
use App\Services\DistanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Controller responsible for calculating real-time delivery pricing.
 */
class OrderPriceController extends Controller
{
    /**
     * Estimates the delivery fee based on distance, package size, and base rates.
     * * @param DeliveryRequestRequest $request Validates lat/lon, booking_date, and package_size
     * @param DistanceService $distanceService The service we documented previously
     * @return JsonResponse
     */
    public function estimate(DeliveryRequestRequest $request, DistanceService $distanceService): JsonResponse
    {
        // 1. Determine Departure Time
        // We default to 9:00 AM of the booking date or today to avoid midnight traffic anomalies
        $departureTime = $request->booking_date 
            ? Carbon::parse($request->booking_date)->setTime(9, 0)->toIso8601String()
            : Carbon::now()->setTime(9, 0)->toIso8601String();

        // 2. Calculate Distance via Service
        $measurement = $distanceService->distanceMeasure(
            'google', // google or haversine - we recommend Google for better accuracy in pricing
            $departureTime,
            (float) $request->pickup_lat,
            (float) $request->pickup_lon,
            (float) $request->delivery_lat,
            (float) $request->delivery_lon
        );

        if (!$measurement['success']) {
            return apiError('Failed to calculate distance: ' . ($measurement['message'] ?? ''), 500);
        }

        // IMPORTANT: Use camelCase keys to match the DistanceService output
        $distance = $measurement['distanceKm'];
        $duration = $measurement['durationMinutes'];

        // 3. Retrieve Pricing Configuration
        $settings = DeliveryFeeSetting::first();

        if (!$settings) {
            return apiError('Delivery pricing settings not configured in database', 404);
        }

        // 4. Extract Rates
        $baseFare    = (float) $settings->base_fare;
        $pricePerKm  = (float) $settings->per_km_fee;
        $packageSize = $request->package_size; // e.g., 'small', 'medium', 'large'

        // Map package size to the corresponding fee from settings
        $packageFees = [
            'small'  => (float) $settings->small_package_fee,
            'medium' => (float) $settings->medium_package_fee,
            'large'  => (float) $settings->large_package_fee,
        ];

        $packageFee = $packageFees[$packageSize] ?? 0;

        // 5. Apply Pricing Formula
        // Formula: (Distance * Rate) + Package Fee. 
        // If the result is lower than the Base Fare, use the Base Fare.
        $calculatedTotal = ($distance * $pricePerKm) + $packageFee;
        $finalPrice = round(max($calculatedTotal, $baseFare), 2);

        // 6. Response Construction
        
        $data = [
            'items'            => $request->items,
            'distance'         => $distance,
            'est_time_minutes' => $duration,
            'base_fare'        => $baseFare,
            'per_km_fee'       => $pricePerKm,
            'package_size'     => $packageSize,
            'package_fee'      => $packageFee,
            'total_fee'        => $finalPrice,
            'currency'         => $settings->currency,
            // 'response_details' => $measurement
        ];

        return apiSuccess('Delivery fee calculated successfully', $data);
    }
}