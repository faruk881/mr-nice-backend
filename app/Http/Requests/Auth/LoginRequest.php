<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email'    => 'required|email',
            'password' => 'required|string|min:8',
            'type'     => 'required|in:customer,courier,admin',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'An email address is required.',
            'email.email'       => 'Please provide a valid email address.',

            'password.required' => 'A password is required.',
            'password.min'      => 'The password must be at least 8 characters long.',

            'type.in'           => 'Please enter a valid user type',
            'type.required'     => 'User type is required.'
        ];
    }
}
