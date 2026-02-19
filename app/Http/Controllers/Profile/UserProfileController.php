<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $user = auth()->user();
            return apiSuccess('Profile loaded', $user);
            
        } catch (\Throwable $e) {
            return apiError($e->getMessage(), $e->getCode(),['debug_message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            // Get the user
            $user = auth()->user();

            // Get the input data
            $data = $request->validated();
        
            // Update the name if exists
            if(isset($data['name'])){
                $user->name = $data['name'];
            }

            // Update the phone if exists
            if(isset($data['phone'])){
                $user->phone = $data['phone'];
            }

            // Update the profile photo
            if(isset($data['profile_photo'])){

                // Delete old profile photo if exists
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                // Store new photo
                $path = $data['profile_photo']->store('/images/profile','public');

                // Save new path
                $user->profile_photo = $path;
            }   

            // Update the user
            $user->save(); 

            return apiSuccess('Profile updated successfully.', $user);
        } catch (\Throwable $e) {
            return apiError($e->getMessage(), $e->getCode(),['debug_message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
