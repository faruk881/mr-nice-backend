<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            // Added regex to prevent HTML/Scripts in the name
            'name' => 'nullable|string|max:255|regex:/^[\pL\s\-\']+$/u',
            
            // Regex ensures it looks like a phone number (numbers, +, -, spaces)
            'phone' => [
                'nullable',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:10',
                Rule::unique('users', 'phone')->ignore(auth()->id()),
            ],
            'vehicle_type' => 'nullable|string|in:bicycle,car,motorbike,cargo-van',
        ];
    }

    public function messages(): array
    {
        return [
            // Name messages
            'name.regex' => 'Names can only contain letters, spaces, hyphens, and apostrophes.',

            // Phone messages
            'phone.regex' => 'Please enter a valid phone number (e.g., +1 234 567 890).',
            'phone.unique' => 'This phone number is already in use.',

            // Profile photo messages
            'profile_photo.image' => 'Profile photo must be a valid image.',
            'profile_photo.mimes' => 'Profile photo must be a JPG, PNG, or WEBP image.',
            'profile_photo.max' => 'Profile photo size must not exceed 2MB.',
        ];
    }
}
