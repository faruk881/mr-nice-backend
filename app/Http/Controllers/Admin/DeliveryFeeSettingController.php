<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBaseFareRequest;
use App\Http\Requests\Settings\UpdateDistanceFeeRequest;
use App\Http\Requests\Settings\UpdateItemTypeFeeRequest;
use App\Models\DeliveryFeeSetting;

class DeliveryFeeSettingController extends Controller
{
    public function index() {

        // Get the first (and only) pricing row
        $prices = DeliveryFeeSetting::first();

        // Check if data exists
        if (!$prices) {
            return apiError('Delivery pricing settings not found', 404,['code'=>'DELIVERY_PRICING_SETTINGS_NOT_FOUND']);
        }

        // Return
        return apiSuccess('Delivery Pricing Settings', $prices);
    }

    public function updateDistanceFee(UpdateDistanceFeeRequest $request){
        try{
            // Get the first (and only) pricing row
            $prices = DeliveryFeeSetting::first();

            // Check if price exists
            if (!$prices) {
                return apiError('Delivery pricing settings not found', 404,['code'=>'DELIVERY_PRICING_SETTINGS_NOT_FOUND']);
            }

            // Check if the price is same
            if ($prices->per_km_fee == $request->input('per_km_fee')) {
                return apiError('You entered same fee.', 400,['code'=>'SAME_FEE']);
            }

            // Update the price
            $prices->update([
                'per_km_fee' => $request->input('per_km_fee'),
                'updated_by' => auth()->user()->id
            ]);

            // Return
            return apiSuccess('Distance pricing updated successfully', $prices);
        }catch(\Throwable $e){
            throw $e;
        }

    }

    public function updateItemTypeFee(UpdateItemTypeFeeRequest $request){
        try {
            // Get the first (and only) pricing row
            $prices = DeliveryFeeSetting::first();

            // Check if price exists
            if (!$prices) {
                return apiError('Delivery fees settings not found', 404 ,['code'=>'DELIVERY_FEES_SETTINGS_NOT_FOUND']);
            }

            // Check if same package price entered
            if($prices->small_package_fee == $request->input('small_package_fee') &&
                $prices->medium_package_fee == $request->input('medium_package_fee') &&
                $prices->large_package_fee == $request->input('large_package_fee')){
                return apiError('You entered same fees.', 400, ['code'=>'SAME_FEE']);
            }


            // Update the price
            $prices->update([
                'small_package_fee' => $request->input('small_package_fee'),
                'medium_package_fee' => $request->input('medium_package_fee'),
                'large_package_fee' => $request->input('large_package_fee'),
                'updated_by' => auth()->user()->id
            ]);

            // Return
            return apiSuccess('Distance fees updated successfully', $prices);
        } catch(\Throwable $e) {
            throw $e;
        }
        
    }
    public function updateBaseFare(UpdateBaseFareRequest $request){
        try {
            // Get the first (and only) pricing row
            $prices = DeliveryFeeSetting::first();

            // Check if price exists
            if (!$prices) {
                return apiError('Delivery fees settings not found', 404, ['code'=>'DELIVERY_FEES_SETTINGS_NOT_FOUND']);
            }

            // Check if same package price entered
            if($prices->base_fare == $request->input('base_fare')){

                return apiError('You entered same fees.', 400, ['code'=>'SAME_FEE']);
            }


            // Update the price
            $prices->update([
                'base_fare' => $request->input('base_fare'),
                'updated_by' => auth()->user()->id
            ]);

            // Return
            return apiSuccess('base fare updated successfully', $prices);
        } catch(\Throwable $e) {
            throw $e;
        }
        
    }
}
