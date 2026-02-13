<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerBecomeCourierRequest;
use App\Models\Role;
use Illuminate\Http\Request;

class CustomerCourierController extends Controller
{
    public function store(CustomerBecomeCourierRequest $request){
        
        try {

            // Get logged in user.
            $user = auth()->user();

            // Get the user profile if exists.
            $profile = $user->courierProfile;

            // If profile exist messages.
            $messages = [
                'pending'  => 'You already submitted a courier application.',
                'approved' => 'You already have a courier profile.',
                'rejected' => 'Your courier application was rejected. Please contact admin.',
            ];

            // Check if profile exists and return custom message.
            if ($profile && isset($messages[$profile->document_status])) {
                return apiError($messages[$profile->document_status], 422);
            }

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

            // Get courier id
            $courierId = Role::where('name','courier')->first()->id;

            // Add courier role
            $user->roles()->syncWithoutDetaching([$courierId]);

            // Retuen message
            return apiSuccess('Courier application submitted successfully. Await admin approval.', $profile);
        } catch (\Throwable $e) {
            return apiError($e->getMessage());
        }
    }
}
