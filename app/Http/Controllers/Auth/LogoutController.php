<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function destroy(Request $request)
    {
        try {

            // Users can log out from active session or all session 
            // 
            if ($request->type == 'all'){
                $request->user()->tokens()->delete();
                $message = "Logged out from all session.";
            } else {
                $request->user()->currentAccessToken()->delete();
                $message = "Logged out from current session.";
            }
            return apiSuccess($message);
        } catch (\Throwable $e) {
            return apiError(
                app()->isLocal() ? $e->getMessage() : 'Logout failed.',
                500
            );
        }
    } //End of logout function
}
