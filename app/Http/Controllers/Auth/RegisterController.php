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
            $userFields = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'type' => $request->type,
                'password' => $request->password,
            ];
            
            // Create the user
            $user = User::create($userFields);

            // Get the role name
            $role = $request->type;

            // find the role
            $userRole = Role::where('name', $role)->first();

            // Check if role exists
            if (!$userRole) {
                DB::rollBack();
                return apiError('Invalid user type', 404, ['code' => 'INVALID_USER_TYPE']);
            }

            // Assign user role
            $user->roles()->attach($userRole->id);

            $data['user'] = new AuthUserResource($user);

            if($role == 'courier') {

                // Handle ID document upload
                $path = $request->file('id_document')->store('courier/id_documents', 'public');

                // Create courier profile
                $profile = $user->courierProfile()->create([
                    'city' => $request->city,
                    'vehicle_type' => $request->vehicle_type,
                    'package_size' => $request->package_size,
                    'id_document' => $path,
                    'document_status' => 'pending',
                ]);
                $data['courier_profile'] = $profile;

            }

            // Send the email otp
            $otpResult = $otpService->sendEmailOtp($user,'register');

            // Check if there is error sending otp
            if (!$otpResult['success']) {
                DB::rollBack();

                return apiError('Unable to send verification code. Please try again later.', 500, ['code'=>'OTP_SEND_FAILED']);
            }

            // Commit the database
            DB::commit();

            // Return the success message
            return apiSuccess('User registered successfully. A verification code has been emailed.',$data);
        } catch (\Throwable $e) {
            // Rollback the database
            DB::rollBack();
            // Rethrow the exception to be handled by the global exception handler
            throw $e;
        }
    }
}
