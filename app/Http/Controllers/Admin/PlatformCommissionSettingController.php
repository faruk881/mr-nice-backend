<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlatformCommissionSettingRequest;
use App\Models\PlatformCommissionSetting;
use Illuminate\Http\Request;

class PlatformCommissionSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get Platform Commission Setting
        $platformCommissionSetting = PlatformCommissionSetting::first();

        // Check if exists
        if (!$platformCommissionSetting) {
            return apiError('Platform Commission Settings not found', 404);
        }

        // Return the platform commission
        return apiSuccess('Platform Commission Settings', $platformCommissionSetting);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlatformCommissionSettingRequest $request)
    {
        // Get platform commission setting
        $platformCommissionSetting = PlatformCommissionSetting::first();

        // Check if data exists
        if (!$platformCommissionSetting) {
            // Create new table if null
            $platformCommissionSetting = new PlatformCommissionSetting();
        }

        // Add values
        $platformCommissionSetting->commission_amount = $request->commission_amount;
        $platformCommissionSetting->commission_percent = $request->commission_percent;
        $platformCommissionSetting->active_commission = $request->active_commission;

        // Track who updated
        $platformCommissionSetting->updated_by = auth()->id();

        // Save to database
        $platformCommissionSetting->save();

        // Return
        return apiSuccess('Platform Commission Settings updated successfully', $platformCommissionSetting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
