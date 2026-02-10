<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordUpdateController extends Controller
{
    public function update(PasswordUpdateRequest $request){
        try{
            $user = auth()->user();

            // Check current password
            if(!Hash::check($request->current_password, $user->password)){
                return apiError('Current password is incorrect',422);
            }

            // Update to new password
            $user->password = $request->new_password;
            $user->save();
            $session = request()->user()->currentAccessToken();
            // Revoke current token
            $session->delete();

            return apiSuccess('Password changed successfully');
        } catch(\Throwable $e){
            return apiError($e->getMessage());
        }
    }
}
