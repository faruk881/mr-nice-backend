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

            // Conditional Courier Fields
            'city'         => 'required_if:type,courier|string|max:255',
            'vehicle_type' => 'required_if:type,courier|in:bicycle,car,motorbike,cargo-van',
            'package_size' => 'nullable|string|max:50',
            'id_document'  => 'required_if:type,courier|file|mimes:jpg,jpeg,png,pdf|max:4096',
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

            // Courier-specific messages
            'city.required_if'         => 'Please specify the city where you will be operating.',
            'vehicle_type.required_if' => 'We need to know what vehicle you use.',
            'vehicle_type.in' => 'Please select a valid vehicle type: bicycle, car, motorbike, cargo-van',
            'id_document.required_if'  => 'A valid ID document (PDF or Image) is required for courier verification.',
            'id_document.mimes'        => 'Your ID must be a JPG, PNG, or PDF file.',
            'id_document.max'          => 'The document file size must not exceed 4MB.',
        ];
    }
}
