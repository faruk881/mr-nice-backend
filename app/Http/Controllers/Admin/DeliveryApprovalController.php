<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryApprovalRequest;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Paginate
        $perPage = $request->input('per_page',10);
        
        // Get pending deliveries
        $pendingDeliveries = Order::where('status','pending_delivery')->paginate($perPage);

        // return
        return apiSuccess('All pending delivery loaded successfully', $pendingDeliveries);

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
                $courierWallet = $order->user->wallet;

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
                    'source'         => 'delivery_commission',
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
