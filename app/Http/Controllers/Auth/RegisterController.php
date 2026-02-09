<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Resources\AuthUserResource;
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

            $otpResult = $otpService->sendEmailOtp($user);

            if (!$otpResult['success']) {
                DB::rollBack();

                return apiError(
                    'Unable to send verification code. Please try again later.',
                    500
                );
            }
            DB::commit();
            return apiSuccess(
                'User registered successfully. A verification code has been sent to your email address.',
                new AuthUserResource($user)
            );

        } catch( \Throwable $e) {
            DB::rollBack();
            return apiError('Registration failed. Please try again later. | ' . $e->getMessage, 500);
        }
    }
}
