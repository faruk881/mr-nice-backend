<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function store(UserRegisterRequest $request, OtpService $otpService) {
        try {
            // Begin DB Transaction
            DB::beginTransaction();

            // Get the validated Fields
            $fields = $request->validated();
            
            // Create the user
            $user = User::create($fields);

            // Get the role name
            $role = $request->type;

            // find the role
            $userRole = Role::where('name', $role)->first();

            // Check if role exists
            if (!$userRole) {
                DB::rollBack();
                return apiError('Invalid user type', 404);
            }

            // Assign user role
            $user->roles()->attach($userRole->id);

            // Send the email otp
            $otpResult = $otpService->sendEmailOtp($user,'register');

            // Check if there is error sending otp
            if (!$otpResult['success']) {
                DB::rollBack();

                return apiError('Unable to send verification code. Please try again later.',500);
            }

            // Commit the database
            DB::commit();

            // Return the success message
            return apiSuccess(
                'User registered successfully. A verification code has been sent to your email address.',
                new AuthUserResource($user)
            );

        } catch( \Throwable $e) {
            DB::rollBack();
            return apiError('Registration failed. Please try again later. | '.$e->getMessage().' | '.$e->getLine(), 500);
        }
    }
}
