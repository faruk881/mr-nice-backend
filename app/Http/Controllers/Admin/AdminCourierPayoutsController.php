<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCourierPayoutStatusRequest;
use App\Jobs\ProcessStripePayout;
use App\Models\Payout;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\CourierPayoutStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Account;
use Stripe\Stripe;

class AdminCourierPayoutsController extends Controller
{
    public function index(Request $request) {

        // Get pagination
        $perPage = $request->query('per_page',10);

        // Get payout history
        $payoutHistory = Payout::paginate($perPage);

        // return
        return apiSuccess('Payout history loaded successfully',$payoutHistory);
    }

    public function update(UpdateCourierPayoutStatusRequest $request, $id) {
        
        // Get payout
        $payout = Payout::where('id',$id)->first();

        // Get the payout status
        $status = $request->status;
        
        // Check if payout exists
        if(!$payout) {
            return apiError('Payout not found',400);
        }
        // Get the user
        $courier = $payout->courier;

        // check if courier exists
        if(!$courier) {
            return apiError('Courier not found',400);
        }

        // check if for duplicate rejected payouts
        if($payout->status === 'rejected' && $request->status === 'reject') {
            return apiError('The payout already '.$payout->status);
        }

        // check for duplicate approved payout
        if(
            $payout->status === 'approved' 
            // || $payout->status === 'processing' 
            || $payout->status === 'paid' 
            // || $payout->status === 'funded' 
            && $request->status === 'approve') {
            return apiError('The payout already '.$payout->status);
        }

        // Prevent double handling
        if ($payout->status !== 'requested') {
            return apiError('This payout can no longer be modified', 409);
        }

        if ($request->status === 'reject') {

            // Start transaction
            DB::beginTransaction();

            try {
                // Get wallet and payout
                $wallet = $payout->wallet;

                // Refund wallet
                WalletTransaction::create([
                    'wallet_id'       => $wallet->id,
                    'type'            => 'credit',
                    'source'          => 'adjustment',
                    'amount'          => $payout->amount,
                    'balance_before'  => $wallet->balance,
                    'balance_after'   => $wallet->balance + $payout->amount,
                    'status'          => 'completed',
                    'metadata'        => [
                        'payout_id' => $payout->id,
                        'reason'    => 'admin_rejected',
                    ],
                ]);

                // Update wallet balance
                $wallet->increment('balance', $payout->amount);

                // Update payout status
                $payout->update([
                    'status' => 'rejected',
                ]);

                // Commit transaction
                DB::commit();

                $payout->courier->notify( 
                    new CourierPayoutStatusNotification($payout, 'requested') 
                );

                // Return success response
                return apiSuccess(
                    'Payout rejected and amount refunded to wallet.',
                    [
                        'payout_id' => $payout->id,
                        'status'    => $payout->status,
                    ]
                );

            } catch (\Throwable $e) {
                DB::rollBack();
                return apiError('Failed to reject payout. '.$e->getMessage(), 500);
            }
        }

        if($request->status === 'pay') {
            try{
                if (!$payout->wallet->user->stripe_user_id) {
                    return apiError('Courier has no Stripe account connected', 422);
                }

                Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

       
                $account = Account::retrieve($courier->stripe_user_id);
    
                if (!$account->charges_enabled && !$account->payouts_enabled) {
                    return apiError('Courier Stripe account is not onboarded', 422);
                }

                $payout->update([
                    'status'       => 'approved',
                    'approved_at'  => now(),
                    'approved_by'  => auth()->id(),
                ]);

                ProcessStripePayout::dispatch($payout->id);

                return apiSuccess('Balance transfered to couriers bank.', [
                    'payout_id' => $payout->id,
                    'status'    => $payout->status,
                ]);

            } catch (\Throwable $e) {
                throw $e;
            }
        }

    }
}
