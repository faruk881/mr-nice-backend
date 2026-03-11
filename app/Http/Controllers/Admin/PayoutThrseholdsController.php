<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PayoutThrseholdsRequest;
use App\Models\PayoutThrsehold;
use Illuminate\Http\Request;

class PayoutThrseholdsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payoutThrseholds = PayoutThrsehold::first();

        return apiSuccess('Payout thrseholds loaded',$payoutThrseholds);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PayoutThrseholdsRequest $request)
    {

        $payoutThrseholds = PayoutThrsehold::updateOrCreate(
            ['id' => 1], // condition
            [
                'minimum_amount' => $request->minimum_amount,
                'maximum_amount' => $request->maximum_amount,
                'set_by' => auth()->user()->id
            ]
        );
    
        return apiSuccess('Payout thresholds updated successfully.',$payoutThrseholds);

    }
}
