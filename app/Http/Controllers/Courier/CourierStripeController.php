<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Stripe;

class CourierStripeController extends Controller
{
    public function redirectToStripe()
    {
        // Get user
        $user = auth()->user();

        // Check if user already connected stripe
        if($user->stripe_user_id) {
            return apiError('Stripe account is already connected', 409, ['code'=>'STRIPE_ALREADY_CONNECTED']);
        }
        
        // Encode the user id
        $state = base64_encode($user->id); // encode courier ID
    
        // Load stripe client id
        $clientId = config('services.stripe.client_id');

        // Redirect callback url
        $redirectUri = route('courier.stripe.callback');
    
        // Stripe connect url
        $url = "https://connect.stripe.com/oauth/authorize?response_type=code&client_id={$clientId}&scope=read_write&redirect_uri={$redirectUri}&state={$state}";
    
        // Return message
        return apiSuccess('Url Generated',$url);
    }

    public function handleStripeCallback(Request $request)
    {
        $code = $request->code;
        $state = $request->state;
    
        if(!$code || !$state) {
            return response()->json(['error'=>'Invalid request'],400);
        }
    
        $userId = base64_decode($state);
        $user = User::findOrFail($userId);
    
        Stripe::setApiKey(config('services.stripe.secret'));
    
        $response = \Stripe\OAuth::token([
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);
    
        $user->stripe_user_id = $response->stripe_user_id;
        $user->save();
    
        // Redirect or return success JSON
        // return redirect('/courier/dashboard')->with('success','Stripe connected!');
        return response()->json('Stripe account connected. Now you can close this page');
    }
}
