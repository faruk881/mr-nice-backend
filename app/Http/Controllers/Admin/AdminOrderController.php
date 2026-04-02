<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AdminOrderRequest $request)
    {
        $perPage = $request->query('per_page', 10);

        $orders = Order::with('refund')

        // Filter by exact status
        ->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        })

        // Filter by Date Range (Start)
        ->when($request->start_date, function ($query, $date) {
            $query->whereDate('booking_date', '>=', $date);
        })

        // Filter by Date Range (End)
        ->when($request->end_date, function ($query, $date) {
            $query->whereDate('booking_date', '<=', $date);
        })

        // Search by Order ID or Customer Name (example)
        ->when($request->search, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                ->orWhere('pickup_address', 'LIKE', "%{$search}%")
                ->orWhere('delivery_address', 'LIKE', "%{$search}%")
                ->orWhere('package_items', 'LIKE', "%{$search}%");
            });
        })
        ->latest('id')
        ->paginate($perPage)
        // This ensures your pagination links keep the filter parameters
        ->withQueryString(); 

        return apiSuccess('Orders loaded', $orders);
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
