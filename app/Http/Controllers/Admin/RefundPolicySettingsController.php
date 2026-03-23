<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundPolicySettingsRequest;
use App\Models\RefundPolicySetting;
use Illuminate\Http\Request;

class RefundPolicySettingsController extends Controller
{
    public function index()
    {
        $refundPolicySetting = RefundPolicySetting::first();

        return apiSuccess('Refund policy setting loaded successfully',$refundPolicySetting);
    }

    public function update(RefundPolicySettingsRequest $request) {
        
        // Get the refund policy settings
        $refundPolicySetting = RefundPolicySetting::first();

        // Check if policy exists
        if(!$refundPolicySetting) {
            return apiError('Refund policy setting not found',404,['code'=>'REFUND_POLICY_SETTING_NOT_FOUND']);
        }

        // Check for already saved refund
        if ($refundPolicySetting->refund_type === $request->refund_type) {
            // If refund type is custom_refund, also check deduction amount
            if ($request->refund_type === 'custom_refund') {
                if ($refundPolicySetting->custom_refund_deduction_amount == $request->custom_refund_deduction_amount) {
                    return apiError(
                        'Refund type and custom deduction amount are already saved',
                        400,
                        ['code' => 'REFUND_TYPE_ALREADY_SAVED']
                    );
                }
            } else {
                // For partial_refund or full_refund
                return apiError(
                    'Refund type already saved',
                    400,
                    ['code' => 'REFUND_TYPE_ALREADY_SAVED']
                );
            }
        }

        // Get the policy
        $refund_type = $request->refund_type;

        // Save the refund type
        $refundPolicySetting->refund_type = $refund_type;
        $refundPolicySetting->custom_refund_deduction_amount = 0.00;


        // Check if custom refund
        if($refund_type == 'custom_refund') {
            $refundPolicySetting->custom_refund_deduction_amount = $request->custom_refund_deduction_amount;
        }

        // Save the policy
        $refundPolicySetting->save();

        // Return
        return apiSuccess('Refund policy setting updated successfully',$refundPolicySetting);


    }
}
