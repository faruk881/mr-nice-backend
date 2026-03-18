<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerNotificationsController extends Controller
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
}
