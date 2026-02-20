<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmMailRequest;
use App\Http\Requests\Auth\OtpResentRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmailVerificationController extends Controller
{
    public function verify(ConfirmMailRequest $request){
        try{

            // Get the user
            $user = User::where('email', $request->email)->first();

            // Check if user exists.
            if(!$user) {
                return apiError('Invalid email or otp');
            }

            // Check if user created using google id.
            if($user->google_id){
                return apiError('User created using google. Please sign in using google.',403);
            }

            // Check if usermail already verified.
            if($user->email_verified_at){
                return apiError('The mail is already verified');
            }

            // Check if the OTP Expires.
            if (
                !$user || 
                !$user->otp || 
                !Hash::check($request->otp, $user->otp) || 
                Carbon::now()->gt($user->otp_expires_at)) {
                return apiError('Invalid or expired OTP, Please request a new one.');
            }   

            // Update the otp status.
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
                'email_verified_at' => Carbon::now(),
            ]);

            // Return success message.
            return apiSuccess('email successfully verified, now you can log in', new AuthUserResource($user));
        } catch(\Throwable $e) {
                return apiError(app()->isLocal() ? $e->getMessage() : 'Something went wrong', 500);
        }
    }

    public function resend(OtpResentRequest $request, OtpService $otpService)
    {
        // Get the user
        $user = User::where('email', $request->email)->first();

        // Generic error (prevents enumeration)
        if (! $user) {
            return apiError('Invalid credentials.', 401);
        }

        // Already verified
        if ($user->email_verified_at) {
            return apiError('This email address has already been verified.',409);
        }

        // Account inactive
        if ($user->status !== 'active') {
            return apiError('Your account is not active.', 403);
        }

        // OTP expired or not generated yet → resend
        if (! $user->otp_expires_at || now()->gt($user->otp_expires_at)) {

            // Send the email otp
            $otpResult = $otpService->sendEmailOtp($user,'register');

            // Check if there is error sending otp
            if (!$otpResult['success']) {

                return apiError('Unable to send verification code. Please try again later.',500);
            }

            return apiSuccess('A verification code has been sent to your email.',null,200);
        }

        // OTP still valid → block resend
        return apiError('A verification code was already sent. Please try again after it expires.',429);


    }
}
