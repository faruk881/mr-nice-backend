<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\AdminGetUsersRequest;
use App\Http\Requests\Admin\AdminUpdateUsersStatusRequest;
use App\Http\Requests\Admin\AdminVerifyCourierRequest;
class AdminUsersController extends Controller
{
    /**
     * Show Couriers.
     */
    public function index(AdminGetUsersRequest $request)
    {
        $search   = $request->input('search');
        $perPage  = $request->input('per_page', 10);
        $sort     = $request->input('sort', 'created_at');
        $userType = $request->input('user_type');
        $filter   = $request->input('filter');
    
        $query = User::query();
    
        // Filter by role
        $query->whereHas('roles', function ($q) use ($userType) {
            $q->where('name', $userType);
        });

        $query->when($filter, function ($q) use ($filter) {
            $q->where('status', $filter);
        });
    
        // Search filter
        $query->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        });
    
        if($userType == 'courier') {
            // Load profile
            $query->with('courierProfile');
        }

    
        // Conditional order counts
        if ($userType === 'courier') {
            $query->withCount([
                'assignedOrders as delivered_orders_count' => function ($q) {
                    $q->where('status', 'delivered');
                }
            ]);
        }
    
        if ($userType === 'customer') {
            $query->withCount([
                'orders as total_orders_count' => function ($q) {
                    $q->whereIn('status', [
                        // 'pending_payment',
                        'pending',
                        'accepted',
                        'pickedup',
                        'pending_delivery',
                        'delivered',
                        // 'cancelled'
                    ]);
                }
            ]);
        }
    
        // Sorting
        $query->orderBy($sort, 'desc');
    
        $users = $query->paginate($perPage);
    
        return apiSuccess('Users loaded successfully', $users);
    }


    public function updateVerification(AdminVerifyCourierRequest $request, string $id)
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
     * Update users status
     */
    public function updateStatus(AdminUpdateUsersStatusRequest $request, string $id)
    {
        $status = $request->input('status');

        // Get courier
        $user = User::where('id', $id)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'courier');
            })
            ->first();

        // User not found
        if (!$user) {
            return apiError(
                'User not found.',
                404,
                ['code' => 'USER_NOT_FOUND']
            );
        }

        // Same status check
        if ($user->status === $status) {
            return apiError(
                "This user is already {$status}.",
                422,
                ['code' => 'SAME_USER_STATUS']
            );
        }

        // Update status
        $user->update([
            'status' => $status
        ]);

        return apiSuccess('User status updated.', $user);
    }

}
