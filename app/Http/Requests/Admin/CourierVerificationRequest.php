<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CourierVerificationRequest extends FormRequest
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
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|max:255',
        ];
    }
    public function messages(): array
    {
        return [
            'status.required' => 'Please select a verification status (approved or rejected).',
            'status.in' => 'The verification status must be either "approved" or "rejected".',
            'rejection_reason.required_if' => 'Please provide a reason for rejection when rejecting a courier application.',
            'rejection_reason.string' => 'The rejection reason must be a valid string.',
            'rejection_reason.max' => 'The rejection reason must not exceed 255 characters.',
        ];
    }
}
