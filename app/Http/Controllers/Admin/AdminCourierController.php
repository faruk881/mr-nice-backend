<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\AdminGetCouriersRequest;
use App\Http\Requests\Admin\AdminVerifyCourierRequest;
class AdminCourierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AdminGetCouriersRequest $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort', 'created_at');
    
        $couriers = User::whereHas('roles', function($query) {
                $query->where('name', 'courier');
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->with('courierProfile')
            ->orderBy($sort, 'desc')
            ->paginate($perPage);
    
        return apiSuccess('All couriers loaded', $couriers);
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

    public function verify(AdminVerifyCourierRequest $request, string $id)
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

        // Check if the courier document is already verified or rejected
        if($courier->courierProfile->document_status == $request->status) {
            return apiError('This courier is document is already '.$request->status.'.', 422);
        }

        if($courier->courierProfile->document_status != 'pending') {
            return apiError('This courier is document is not pending.', 422);
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
