<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCourierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get the courier
        $courier = User::all();

        // Return the success message
        return apiSuccess('All couriers loaded', $courier);
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
    public function update(Request $request, string $id)
    {
        //
    }

    public function verify(Request $request, string $id)
    {
        // Get the courier form user table
        $courier = User::where('id', $id)
        ->whereHas('roles', function($query) {
            $query->where('name', 'courier');
        })
        
        ->first();

        // Check if courier exists
        if (!$courier) {
            return apiError('Courier not found.', 404);
        }

        if($courier->courierProfile->document_status == 'approved') {
            return apiError('This courier is already approved.', 422);
        }

        // Update the courier profile document status
        $courier->courierProfile->update([
            'document_status' => $request->status,
            'document_reject_reason' => $request->status == 'rejected' ? $request->rejection_reason : null,
        ]);

        // Return the success message
        return apiSuccess('Courier verification status updated.', $courier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
