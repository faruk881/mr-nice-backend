<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request) 
    {
        // paginate
        $perPage = $request->query('per_page',10);

        // Get the user
        $user = auth()->user();

        // Check if user exists
        if(!$user) {
            return apiError('Notification not found', 404, ['code'=>'NOTIFICATION_NOT_FOUND']);
        }

        // Get notification
        $notifications = $user->notifications()->latest()->paginate($perPage);

        // Return
        return apiSuccess('Notifications loaded',$notifications);
    }

    public function update($id)
    {
        $user = auth()->user();

        if (!$user) {
            return apiError('User not found', 404, ['code' => 'USER_NOT_FOUND']);
        }
    
        // Option 1: Mark all unread notifications as read
        // $user->unreadNotifications->markAsRead();
    
        // Option 2: If you want to mark a single notification by ID

        $notificationId = $id;
        $notification = $user->notifications()->where('id', $notificationId)->first();
        if ($notification) {
            $notification->markAsRead();
        }

    
        return apiSuccess('Notifications marked as read');
    }
}
