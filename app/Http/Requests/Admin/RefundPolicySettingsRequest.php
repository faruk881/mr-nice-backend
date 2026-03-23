<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RefundPolicySettingsRequest extends FormRequest
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
            "refund_type" => "required|in:full_refund,partial_refund,custom_refund",
            "custom_refund_deduction_amount" => "required_if:refund_type,custom_refund|nullable|numeric|min:0",
        ];
    }

    public function messages(): array
    {
        return [
            "refund_type.required" => "The refund type is required and must be one of: full_refund, partial_refund, or custom_refund",
            "refund_type.in" => "The refund type must be one of: full_refund, partial_refund, or custom",
            "custom_refund_deduction_amount.required_if" => "The custom refund deduction amount is required when refund type is custom_refund",
            "custom_refund_deduction_amount.numeric" => "The custom refund deduction amount must be a number",
            "custom_refund_deduction_amount.min" => "The custom refund deduction amount must be greater than or equal to 0",
        ];
    }
}
