<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemTypeFeeRequest extends FormRequest
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
            'small_package_fee' => 'required|numeric|min:0',
            'medium_package_fee' => 'required|numeric|min:0',
            'large_package_fee' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'small_package_fee.required' => 'Small package fee is required',
            'small_package_fee.numeric' => 'Small package fee must be a number',
            'small_package_fee.min' => 'Small package fee must be greater than 0',

            'medium_package_fee.required' => 'Medium package fee is required',
            'medium_package_fee.numeric' => 'Medium package fee must be a number',
            'medium_package_fee.min' => 'Medium package fee must be greater than 0',

            'large_package_fee.required' => 'Large package  fee is required',
            'large_package_fee.numeric' => 'Large package fee must be a number',
            'large_package_fee.min' => 'Large package fee must be greater than 0'
        ];
    }
}
