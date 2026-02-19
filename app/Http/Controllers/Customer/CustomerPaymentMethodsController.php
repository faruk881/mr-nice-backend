<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Customer as StripeCustomer;
use Stripe\SetupIntent;

class CustomerPaymentMethodsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Check if user has a Stripe customer ID
        if (!$user->stripe_customer_id) {
            return apiSuccess('No saved payments found.', ['payment_methods' => []]);
        }

        // Set Stripe secret key
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Retrieve all saved cards for this customer
            $paymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'card', // only cards
            ]);

            // Format the response if needed
            $savedCards = $paymentMethods->data ?? [];

            return apiSuccess('Saved payment methods retrieved successfully.', [
                'payment_methods' => $savedCards
            ]);

        } catch (\Throwable $e) {
            return apiError($e->getMessage(), $e->getCode(),['debug_message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Get the user
        $user = auth()->user();

        // Set Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Create Stripe customer if not exists
            if (!$user->stripe_customer_id) {
                $stripeCustomer = StripeCustomer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);

                $user->stripe_customer_id = $stripeCustomer->id;
                $user->save();
            } else {
                // Retrieve existing customer (optional, ensures it exists in Stripe)
                $stripeCustomer = StripeCustomer::retrieve($user->stripe_customer_id);
            }

            // Create a SetupIntent to securely add a payment method
            $setupIntent = SetupIntent::create([
                'customer' => $user->stripe_customer_id,
            ]);

            // Return the client_secret to frontend to complete setup with Stripe.js
            return response()->json([
                'success' => true,
                'message' => 'Setup intent created successfully.',
                'data' => [
                    'client_secret' => $setupIntent->client_secret,
                    'publishable_key' => config('services.stripe.publishable'),
                ],
            ]);

        } catch (\Exception $e) {
            // Handle any Stripe API errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SetupIntent: ' . $e->getMessage(),
            ], 500);
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
