<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourierDeliveryController extends Controller
{
    /**
     * Display All Deliveries for the authenticated courier.
     */
    
    public function index(Request $request)
        {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'filter' => 'sometimes|nullable|in:active,completed,cancelled,all',
        ]);

        // Per page default
        $perPage = $request->query('per_page', 10);

        // Filter value
        $filter = $request->query('filter', 'active');

        // Base query
        $query = Order::where('courier_id', auth()->id());

        // Apply filter
        switch ($filter) {
            case 'active':
                $query->whereIn('status', ['accepted', 'pickedup']);
                break;

            case 'completed':
                $query->where('status', 'delivered');
                break;

            case 'cancelled':
                $query->where('status', 'cancelled');
                break;

            case 'all':
                $query->whereIn('status', ['accepted', 'pickedup', 'delivered', 'cancelled']);
                break;
        }

        // Get paginated results
        $deliveries = $query
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        // return
        return apiSuccess('Deliveries retrieved successfully', $deliveries);
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
        // Get the order
        $delivery = Order::where('id', $id)
            ->where('courier_id', auth()->id())
            ->with('customer:id,name,phone,profile_photo')
            ->first();

        // Check if order exists
        if (!$delivery) {
            return apiError('Delivery not found.', 404);
        }

        // return
        return apiSuccess('Delivery details retrieved successfully.', $delivery);
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

    public function pickup($id) {

        // Get the order
        $delivery = Order::where('id', $id)
            ->where('status', 'pending')
            ->first();

        // check of order exists
        if (!$delivery) {
            return apiError('Delivery not found or cannot be picked up.', 404);
        }

        // update order status
        $delivery->status = 'pickedup';
        $delivery->courier_id = auth()->id();
        $delivery->save();

        // return
        return apiSuccess('Delivery marked as picked up.', $delivery);
    }

    public function deliver(Request $request, $id) {
        // Validate input
        $request->validate([
            'delivery_proof' => 'required|image|max:10240', // 10MB
        ]);

        // Find order first (before storing image)
        $delivery = Order::where('id', $id)
            ->where('courier_id', auth()->id())
            ->where('status', 'pickedup')
            ->first();

        // If order not found or not in correct status, return error
        if (!$delivery) {
            return apiError('Delivery not found or cannot be delivered.', 404);
        }

        // Start transaction to ensure data integrity
        DB::beginTransaction();

        // Handle delivery proof upload and update order status
        try {
            // Get the file
            $file = $request->file('delivery_proof');

            // Safer filename
            $filename = 'delivery_'.$delivery->id.'_'.Str::random(10).'.'.$file->getClientOriginalExtension();

            // Store the file and get the path
            $path = $file->storeAs('courier/delivery_proofs', $filename, 'public');

            // Update order status and delivery proof path
            $delivery->update([
                'status' => 'pending_delivery',
                'delivery_proof' => $path,
            ]);

            // Commit transaction
            DB::commit();

            // Return success message
            return apiSuccess('Order marked as pending delivery.', $delivery);

        } catch (\Throwable $e) {

            // Rollback transaction
            DB::rollBack();

            // Log the error for debugging (optional)
            throw $e;
        }
    }
}
