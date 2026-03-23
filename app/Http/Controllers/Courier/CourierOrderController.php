<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderStatusNotification;
use Illuminate\Http\Request;

class CourierOrderController extends Controller
{
    public function index(Request $request)
    {
        // per page and filters
        $perPage = $request->query('per_page', 10);
        $size = $request->query('size', 'all');
        $search = $request->query('search'); // 🔍 search input

        // Base query
        $query = Order::where('status', 'pending')
            ->whereNull('courier_id');

        // filter by package size
        switch ($size) {
            case 'small':
            case 'medium':
            case 'large':
                $query->where('package_size', $size);
                break;
            case 'all':
            default:
                break;
        }

        // Search functionality
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%");
                // add more fields if needed
            });
        }

        // paginate
        $orders = $query->latest()->paginate($perPage);

        return apiSuccess('Pending orders retrieved successfully', $orders);
    }

    public function show($orderNumber) {
        // Validate order number
        if (!str_starts_with(strtolower($orderNumber), 'lx')) {
            return apiError('Invalid order number format. The order number starts with LX.', 422, [
                'code' => 'INVALID_ORDER_NUMBER'
            ]);
        }

        // Show order details
        $order = Order::where('order_number', $orderNumber)->where('status', 'pending')->with('customer:id,name,phone,profile_photo')->first();

        // Check if order exists and is pending
        if (!$order) {
            return apiError('Order not found or already accepted.', 404, ['code'=>'ORDER_NOT_FOUND']);
        }

        // Return response
        return apiSuccess('Order details retrieved successfully.', $order);
    }

    public function accept($orderNumber) {

        // Validate order number
        if (!str_starts_with(strtolower($orderNumber), 'lx')) {
            return apiError('Invalid order number format. The order number starts with LX.', 422, [
                'code' => 'INVALID_ORDER_NUMBER'
            ]);
        }

        // Get the order
        $order = Order::where('order_number', $orderNumber)->where('status', 'pending')->first();

        // Check if order exists and is pending
        if (!$order) {
            return apiError('Order not found or already accepted.', 404, ['code'=>'ORDER_NOT_FOUND']);
        }

        // Check if the courier accepting his own order.
        if($order->customer_id == auth()->id()) {
            return apiError('You cannot accept your own order.', 422, ['code'=>'YOUR_ORDER']);
        }
        
        // Update the order
        $order->update([
            'courier_id' => auth()->id(),
            'status' => 'accepted',
        ]);

        // Sent notification
        $order->customer->notify( 
            new OrderStatusNotification($order, 'accepted') 
        ); 

        // Return response
        return apiSuccess('Order accepted successfully.', $order);
    }
}
