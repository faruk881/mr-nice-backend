<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;

class CourierPaymentMethodsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courier = auth()->user();

        $data = [
            'stripe_connected' => false,
            'stripe_onboarded' => false,
            'onboard_url' => null,
            'stripe_connect_url' => null,
            'manage_stripe_url' => null, // link to manage Stripe account
        ];

        if ($courier->stripe_user_id) {
            $data['stripe_connected'] = true;

            Stripe::setApiKey(config('services.stripe.secret'));

            try {
                $account = Account::retrieve($courier->stripe_user_id);

                if ($account->charges_enabled && $account->payouts_enabled) {
                    // Fully onboarded
                    $data['stripe_onboarded'] = true;

                    // Create a login link to Stripe dashboard
                    // $loginLink = $account->createLoginLink($courier->stripe_user_id); // for express account
                    // $data['manage_url'] = $loginLink->url;
                    $data['manage_url'] = "https://dashboard.stripe.com";
                } else {
                    // Not fully onboarded → create onboarding link
                    $accountLink = AccountLink::create([
                        'account' => $courier->stripe_user_id,
                        'refresh_url' => env('APP_FRONTEND_URL') . '/me/payment-management',
                        'return_url' => url('/api/stripe/onboard/success'),
                        'type' => 'account_onboarding',
                    ]);

                    $data['onboard_url'] = $accountLink->url;
                }
            } catch (\Exception $e) {
                return apiError('Stripe account check failed: ' . $e->getMessage(), 500, ['code' => 'STRIPE_ACCOUNT_CHECK_FAILED']);
            }

            return apiSuccess('Payment method loaded', $data);
        }

        // Stripe not connected → provide Stripe Connect OAuth URL
        $state = base64_encode($courier->id); // encode courier ID
        $clientId = config('services.stripe.client_id');
        $redirectUri = route('courier.stripe.callback');

        $data['stripe_connect_url'] = "https://connect.stripe.com/oauth/authorize?response_type=code&client_id={$clientId}&scope=read_write&redirect_uri={$redirectUri}&state={$state}";

        return apiSuccess('Payment method loaded', $data);
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
    public function update(Request $request, string $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
