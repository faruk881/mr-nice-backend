<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserProfileViewResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            throw $e;
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
     * Display view profile for edit. for both Courier and Customer
     */
    public function show()
    {
        $user = auth()->user();
        $role = str_replace('role:', '', $user->currentAccessToken()->abilities)[0];
        $userArray = $user->toArray();
        $userArray['role'] = $role;

        return apiSuccess('Profile loaded', $userArray);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        DB::beginTransaction();

        try {

            // Update user details
            $user->fill([
                'name'  => $data['name']  ?? $user->name,
                'phone' => $data['phone'] ?? $user->phone,
            ]);


            // Update profile photo
            if (isset($data['profile_photo'])) {

                // Delete old photo
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }

                $file = $data['profile_photo'];

                $filename = 'profile_'.$user->id.'_'.Str::random(8).'.'.$file->getClientOriginalExtension();

                $path = $file->storeAs('images/profile', $filename, 'public');

                $user->profile_photo = $path;
            }

            // save information
            $user->save();

            // Save vehicle type if courier
            if (isset($data['vehicle_type']) && $user->courierProfile ) {
                $user->courierProfile->update([
                    'vehicle_type' => $data['vehicle_type']
                ]);
            }


            // Commit transaction
            DB::commit();

            // Return success message
            return apiSuccess('Profile updated successfully.', $user);

        } catch (\Throwable $e) {

            // Rollback transaction
            DB::rollBack();

            throw $e;
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
