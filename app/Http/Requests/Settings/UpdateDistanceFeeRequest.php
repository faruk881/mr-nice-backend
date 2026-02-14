<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDistanceFeeRequest extends FormRequest
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
            'per_km_fee' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'per_km_fee.required' => 'Per km fee is required',
            'per_km_fee.numeric' => 'Per km fee must be a number',
            'per_km_fee.min' => 'Per km fee must be greater than 0'
        ];
    }
}
