<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryApprovalRequest;
use App\Http\Requests\Admin\ViewDeliveriesRequest;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ViewDeliveriesRequest $request)
    {
        // Pagination and filters
        $perPage = $request->input('per_page', 10);
        $filter = $request->input('filter', 'all'); // e.g., 'pending_payment','pending','accepted','pickedup','pending_delivery','delivered','cancelled','all'
        $search = $request->input('search', '');
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
    
        // Base query
        $query = Order::query();
    
        // Apply filter if not 'all'
        if (!empty($filter) && $filter !== 'all') {
            $query->where('status', $filter);
        }
    
        // Apply search if provided (assuming searching by order number or customer name)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%");
            });
        }
    
        // Apply sorting
        if(!empty($sort) && !empty($order)) {
            $query->orderBy($sort, $order);
        }
    
        // Paginate
        $pendingDeliveries = $query->paginate($perPage);
    
        // Return response
        return apiSuccess('Deliveries loaded successfully', $pendingDeliveries);
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
    public function update(DeliveryApprovalRequest $request, string $id)
    {
        // Get the status
        $status = $request->status;

        // Get the order
        $order = Order::where('id', $id)->where('status', 'pending_delivery')->first();

        // Check if order exists
        if(!$order) {
            return apiError('Order not found or cannot be modified', 404);
        }
        
        // Approve the courier
        if($status == 'approve') {
            try {
                /**
                * Update courier wallet
                */
                
                // Get the wallet from order
                $courierWallet = $order->courier->wallet;

                // Check if wallet exists
                if (!$courierWallet || $courierWallet->status !== 'active') {
                    throw new \Exception('Creator wallet not available');
                }

                // Get commission before and after
                $balanceBefore = $courierWallet->balance;
                $balanceAfter  = $balanceBefore + $order->courier_commission;

                // Start Transaction
                DB::beginTransaction();
                // Update the courier wallet
                $courierWallet->update([
                    'balance' => $balanceAfter
                ]);

                // Update the wallet
                WalletTransaction::create([
                    'wallet_id'      => $courierWallet->id,
                    'order_id'        => $order->id,
                    'type'           => 'credit',
                    'source'         => 'delivery_commission', //'delivery_commission','refund','adjustment','payout','payout_request','payout_failed','payout_canceled'
                    'amount'         => $order->courier_commission,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'status'         => 'completed',
                ]);

                /**
                * Update admin wallet
                */

                // Get the wallet from order
                $adminWallet = auth()->user()->wallet;

                // Check if wallet exists
                if (!$adminWallet || $adminWallet->status !== 'active') {
                    throw new \Exception('Admin wallet not available');
                }

                // Get commission before and after
                $balanceBefore = $adminWallet->balance;
                $balanceAfter  = $balanceBefore + $order->admin_commission;

                // Update the courier wallet
                $adminWallet->update([
                    'balance' => $balanceAfter
                ]);

                // Update the wallet
                WalletTransaction::create([
                    'wallet_id'      => $adminWallet->id,
                    'order_id'        => $order->id,
                    'type'           => 'credit',
                    'source'         => 'delivery_commission',
                    'amount'         => $order->courier_commission,
                    'balance_before' => $balanceBefore,
                    'balance_after'  => $balanceAfter,
                    'status'         => 'completed',
                ]);

                // Update the order
                $order->update([
                    'status' => 'delivered'
                ]);

                DB::commit();

                return apiSuccess('Delivery approved successfully', $order);
            } catch(\Throwable $e) {
                DB::rollback();
                throw $e;

            }

        }






    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
