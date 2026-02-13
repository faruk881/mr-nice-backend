<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateDistancePriceRequest;
use App\Http\Requests\Settings\UpdateItemTypePriceRequest;
use App\Models\DeliveryPricingSetting;

class DeliveryPricingSettingController extends Controller
{
    public function index(){

        try {

            // Get the first (and only) pricing row
            $prices = DeliveryPricingSetting::first();

            // Check if data exists
            if (!$prices) {
                return apiError('Delivery pricing settings not found', 404);
            }

            // Return
            return apiSuccess('Delivery Pricing Settings', $prices);
        } catch (\Throwable $e) {
            return apiError($e->getMessage().' | '.$e->getLine());
        }
    }

    public function updateDistancePrice(UpdateDistancePriceRequest $request){
        try{
            // Get the first (and only) pricing row
            $prices = DeliveryPricingSetting::first();

            // Check if price exists
            if (!$prices) {
                return apiError('Delivery pricing settings not found', 404);
            }

            // Check if the price is same
            if ($prices->price_per_km == $request->input('price_per_km')) {
                return apiError('You entered same price.', 400);
            }

            // Update the price
            $prices->update([
                'price_per_km' => $request->input('price_per_km')
            ]);

            // Return
            return apiSuccess('Distance pricing updated successfully', $prices);
        }catch(\Throwable $e){
            return apiError($e->getMessage().' | '.$e->getLine());
        }

    }

    public function updateItemTypePrice(UpdateItemTypePriceRequest $request){
        try {
            // Get the first (and only) pricing row
            $prices = DeliveryPricingSetting::first();

            // Check if price exists
            if (!$prices) {
                return apiError('Delivery pricing settings not found', 404);
            }

            // Check if same package price entered
            if($prices->small_package_price == $request->input('small_package_price') &&
                $prices->medium_package_price == $request->input('medium_package_price') &&
                $prices->large_package_price == $request->input('large_package_price')){
                return apiError('You entered same prices.', 400);
            }


            // Update the price
            $prices->update([
                'small_package_price' => $request->input('small_package_price'),
                'medium_package_price' => $request->input('medium_package_price'),
                'large_package_price' => $request->input('large_package_price')
            ]);

            // Return
            return apiSuccess('Distance pricing updated successfully', $prices);
        } catch(\Throwable $e) {
            return apiError($e->getMessage().' | '.$e->getLine());
        }
        
    }
}
