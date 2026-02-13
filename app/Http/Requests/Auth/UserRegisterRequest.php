<?php

namespace App\Http\Requests\Auth;

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
            // Added regex to prevent HTML/Scripts in the name
            'name' => 'required|string|max:255|regex:/^[\pL\s\-\']+$/u',
            
            // rfc,dns ensures it's a real-looking email address
            'email' => 'required|email:rfc,dns|unique:users,email',
            
            // Regex ensures it looks like a phone number (numbers, +, -, spaces)
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users,phone',
            
            // Check user type courier or admin
            'type' => 'required|in:customer,courier',
            
            // 'Password::defaults()' follows your App's security policy (e.g. mixed case)
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ];
    }

        public function messages(): array
    {
        return [
            // Name messages
            'name.required' => 'Please enter your full name.',
            'name.regex' => 'Names can only contain letters, spaces, hyphens, and apostrophes.',
            
            // Email messages
            'email.required' => 'An email address is required to create an account.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered. Try logging in instead.',
            
            // Phone messages
            'phone.required' => 'A phone number is required for delivery updates.',
            'phone.regex' => 'Please enter a valid phone number (e.g., +1 234 567 890).',
            'phone.unique' => 'This phone number is already in use.',
            
            // User Type messages
            'type.in' => 'Please select a valid role: either Customer or Courier.',
            
            // Password messages
            'password.required' => 'A secure password is required.',
            'password.min' => 'Your password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
