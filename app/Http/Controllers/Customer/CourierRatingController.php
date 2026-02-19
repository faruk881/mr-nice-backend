<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CourierRatingRequest;
use App\Models\CourierRating;
use App\Models\Order;
use Illuminate\Http\Request;

class CourierRatingController extends Controller
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
    public function store(CourierRatingRequest $request)
    {
        try {
            // Get the order id and ratings
            $orderId = $request->order_id;
            $rating = $request->rating;

            // Get the order
            $order = Order::find($orderId);

            // Check if order exists
            if(!$order) {
                return apiError('Order not found', 404);
            }
            
            // Check if user is rating with his own order
            if($order->customer_id !== auth()->id()) {
                return apiError('You are not authorized to update this order', 403);
            }

            // Check if order delivered
            if($order->status == 'delivered') {
                $rating = CourierRating::create([
                    'customer_id' => auth()->id(),
                    'courier_id' => $order->courier_id,
                    'rating' => $rating,
                    'order_id' => $order->id
                ]);
            } else {
                return apiError('Order not delivered',403);
            }

            return apiSuccess('Rating submitted successfully.', $rating);
        } catch (\Throwable $e) {
            return apiError($e->getMessage(), $e->getCode(),['debug_message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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
