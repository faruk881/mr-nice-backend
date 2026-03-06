<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PlatformCommissionSettingRequest extends FormRequest
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
            'commission_amount' => 'nullable|numeric|min:0',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'active_commission' => 'nullable|in:commission_amount,commission_percent',
        ];
    }

    public function messages(): array
    {
        return [
            'commission_amount.numeric' => 'The commission amount must be a number.',
            'commission_amount.min' => 'The commission amount cannot be negative.',

            'commission_percent.numeric' => 'The commission percent must be a number.',
            'commission_percent.min' => 'The commission percent cannot be negative.',
            'commission_percent.max' => 'The commission percent cannot exceed 100.',

            'active_commission.in' => 'The active commission must be either "commission_amount" or "commission_percent".',
        ];
    }
}
