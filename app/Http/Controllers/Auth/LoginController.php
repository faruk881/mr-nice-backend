<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function store(LoginRequest $request, OtpService $otpService){
        //Get the user
        $user = User::where('email', $request->email)->first();

        // Generic error (prevents enumeration)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return apiError('Invalid login credentials.', 401);
        }
        
        // Check if email verified.
        if (! $user->email_verified_at) {

            if ( ! $user->otp_expires_at || Carbon::now()->gt($user->otp_expires_at)) {
                $otpService->sendEmailOtp($user,'register');
                return apiError('error with mail sending',403);
            }

            return apiError(
                'Your email address is not verified. A verification code has been sent.',
                403
            );
        }



        // Account status check
        if ($user->status !== 'active') {
            return apiError('Your account is not active.', 403);
        }

        // Check the role name
        $roleName = $request->type;
        if (!$user->roles()->where('name', $roleName)->exists()) {
            return apiError('You do not have access to this role.', 403);
        }

        // Check for courier pending document status
        if ($roleName === 'courier' && $user->courierProfile->document_status != 'verified') {
            return response()->json([
                'status' => 'pending-document-verification',
                'message' => 'Cannot login as courier. Document verificaiton is pending'
                ],403);
        }

        // Check users login session. if 3 session exists then log out from all session and login.
        $activeSessions = $user->tokens()->count();
        if($activeSessions >= 10) {
            $user->tokens()->delete();
        }

        // Create usertoken. also save the device name
        $token = $user->createToken($request->device_name ?? 'web',['role:'.$roleName,])->plainTextToken;

        // Return success message.
        return apiSuccess('Login successful.', [
            'user'  => $user,
            'courier_profile' => $user->courierProfile,
            'role' => $roleName,
            'token' => $token,
        ]);
    }
    
}
