<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|max:20|unique:users,phone',
            'user_type' => 'required|in:customer,courier',
            'password' => 'required|min:8|confirmed',
        ];
    }

        public function messages(): array
    {
        return [
            'name.required'     => 'The name field is required.',
            'name.max'          => 'The name may not exceed 255 characters.',

            'email.required'    => 'The email address is required.',
            'email.email'       => 'Please provide a valid email address.',
            'email.unique'      => 'This email address is already registered.',

            'password.required' => 'The password field is required.',
            'password.min'      => 'The password must be at least 8 characters long.',
            'password.confirmed'=> 'The passwords do not match.',

            'user_type.required'=> 'The user type is required.',
            'user_type.in'      => 'The user type must be either customer or courier.',

            'phone.string'      => 'The phone number must be a string',
            'phone.max'         => 'The phone number may not exceed 20 characters.',
            'phone.unique'      => 'This phone number is already used.',
            'phone.required'    => 'The phone number is required.',
        ];
    }
}
