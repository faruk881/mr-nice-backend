<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function store(ForgotPasswordRequest $request, OtpService $otpService) {

        try {
            // Get the user
            $user = User::where('email', $request->email)->first();

            // Check if user exists
            if (! $user) {
                return apiSuccess('If the email exists, a verification code has been sent.');
            }

            // Check if user is created using google id.
            if($user->google_id){
                return apiError('Password reset is not available for Google-authenticated accounts.', 422, ['code'=>'GOOGLE_ACCOUNT']);
            }

            // Check if user email is verified.
            // if (! $user->email_verified_at) {
            //     return apiError('You cannot request password reset until you verify email first');
            // }

            // OTP expired or not generated yet → resend
            if (! $user->otp_expires_at || Carbon::now()->gt($user->otp_expires_at)) {
                $otpService->sendEmailOtp($user,'password_reset');

                return apiSuccess('A verification code has been sent to your email.',$user->email);
            }

            // OTP already valid → block resend
            return apiError('A verification code was already sent. Please try again after it expires.',429,['code'=>'OTP_ALREADY_SENT']);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function verify(PasswordResetRequest $request){
        try {

            // Get the user.
            $user = User::where('email', $request->email)->first();

            // Check if user exists and otp valid.
            if (! $user || ! $user->otp || ! Hash::check($request->otp, $user->otp) || now()->gt($user->otp_expires_at)) {
                return apiError('Invalid or expired OTP', 422, ['code'=>'INVALID_OTP']);
            }

            // Generate password reset table
            $plainToken = Str::random(64);

            // Update the database
            $user->update([
                'password_reset_token' => hash('sha256', $plainToken),
                'password_reset_expires_at' => now()->addMinutes(10),
                'otp_verified_at' => Carbon::now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            // Check if user email is verified.
            if (! $user->email_verified_at) {
                $user->update([
                    'email_verified_at' => Carbon::now(),
                ]);
            }

            // revoke all tokens
            $user->tokens()->delete();

            // Prepare the return data
            $data['email'] = $user->email;
            $data['password_reset_token'] = $plainToken;
            
            // Return the data with success message
            return apiSuccess('OTP Verified Successfully',$data);
        } catch(\Throwable $e) {
            throw $e;
        }
    }

    public function reset(UpdatePasswordRequest $request){
        try {
            // Get the user
            $user = User::where('email', $request->email)
                ->where('password_reset_token', hash('sha256', $request->password_reset_token))
                ->where('password_reset_expires_at', '>', now())
                ->first();

            // Check if user exists
            if (! $user) {
                return apiError('Invalid or expired reset token', 422, ['code'=>'INVALID_RESET_TOKEN']);
            }

            // Update the user's password
            $user->update([
                'password' => $request->password,
                'password_reset_token' => null,
                'password_reset_expires_at' => null,
                'otp_verified_at' => null,
            ]);

            // revoke all tokens
            $user->tokens()->delete();

            // return the message
            return apiSuccess('Password reset successful');
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}
