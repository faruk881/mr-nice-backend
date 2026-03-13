<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\PayoutThrsehold;
use App\Models\WalletTransaction;
use App\Notifications\CourierPayoutStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourierPayoutsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Get the courier
            $courier = auth()->user();

            // Get Payout Thrsehold
            $payoutThrsehold = PayoutThrsehold::first();

            // Set min amount
            $minAmount = $payoutThrsehold->minimum_amount;

            // Set max amount
            $maxAmount = $payoutThrsehold->maximum_amount;

            // Get the wallet
            $wallet = $courier->wallet;

            // Get payout amount
            $payoutAmount = $wallet->balance;

            // Check if stripe account is connected
            if(!$courier->stripe_user_id) {
                $error = [
                    'stripe-account-status' => 'not-connected',
                ];
                return apiError('Stripe account is not connected',409,$error);
            }

            // Check minimum payout amount
            if( $payoutAmount < $minAmount ) {
                
                $error = [
                    'min_amount' => 'false'
                ];
                return apiError('Minimum payout amount is '.$minAmount.' USD', 422,$error);
            }

            // Check maximum payout amount
            if( $payoutAmount > $maxAmount ) {
                
                $error = [
                    'max_amount' => 'false'
                ];
                return apiError('The maximum payout amount is '.$maxAmount.' USD', 422,$error);
            }


            // Start Transaction
            DB::beginTransaction();
            $payout = Payout::create([
                'courier_id'      => $courier->id,
                'wallet_id'    => $wallet->id,
                'amount'       => $payoutAmount,
                'currency'     => "CHF",
                'status'       => 'requested',
                'requested_at' => now(),
            ]);

            // Lock funds (debit wallet)
            WalletTransaction::create([
                'wallet_id'       => $wallet->id,
                'order_id'         => null,
                'type'            => 'debit',
                'source'          => 'payout_request',
                'amount'          => $payoutAmount,
                'balance_before'  => $wallet->balance,
                'balance_after'   => $wallet->balance - $payoutAmount,
                'status'          => 'completed',
                'metadata'        => [
                    'payout_id' => $payout->id,
                ],
            ]);

            // Update wallet balance
            $wallet->decrement('balance', $payoutAmount);

            // Commit
            DB::commit();

            // Sent notification
            $payout->courier->notify( 
                new CourierPayoutStatusNotification($payout, 'requested') 
            );

            // Prepare Data
            $data = [
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
                'status' => $payout->status
            ];

            // return
            return apiSuccess('Payout request submitted successfully',$data);

        } catch(\Throwable $e) {
            // Rollback of error
            DB::rollback();
            return apiError('Failed to create payout request '.$e->getMessage(), 500);

        }

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
