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
        // per page and status filters
        $perPage = $request->query('per_page', 10);
        $size = $request->query('size', 'all');


        // Get pending orders that are not yet accepted by any courier
        $query = Order::where('status', 'pending')
            ->whereNull('courier_id');

        // filter by package size if provided
        switch ($size) {
            case 'small':
                $query->where('package_size', 'small');
                break;
            case 'medium':
                $query->where('package_size', 'medium');
                break;
            case 'large':
                $query->where('package_size', 'large');
                break;
            case 'all':
            default:
                // no filtering
                break;
        }

        // paginate and return
        $orders = $query->paginate($perPage);

        // Return response
        return apiSuccess('Pending orders retrieved successfully', $orders);
    }

    public function show($orderId) {
        // Show order details
        $order = Order::where('id', $orderId)->where('status', 'pending')->with('customer:id,name,phone,profile_photo')->first();

        // Check if order exists and is pending
        if (!$order) {
            return apiError('Order not found or already accepted.', 404);
        }

        // Return response
        return apiSuccess('Order details retrieved successfully.', $order);
    }

    public function accept($orderId) {
        // Get the order
        $order = Order::where('id', $orderId)->where('status', 'pending')->first();

        // Check if order exists and is pending
        if (!$order) {
            return apiError('Order not found or already accepted.', 404);
        }

        // Check if the courier accepting his own order.
        if($order->customer_id == auth()->id()) {
            return apiError('You cannot accept your own order.', 422);
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
