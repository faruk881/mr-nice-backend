<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemTypePriceRequest extends FormRequest
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
            'small_package_price' => 'required|numeric|min:0',
            'medium_package_price' => 'required|numeric|min:0',
            'large_package_price' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'small_package_price.required' => 'Small package price is required',
            'small_package_price.numeric' => 'Small package price must be a number',
            'small_package_price.min' => 'Small package price must be greater than 0',

            'medium_package_price.required' => 'Medium package price is required',
            'medium_package_price.numeric' => 'Medium package price must be a number',
            'medium_package_price.min' => 'Medium package price must be greater than 0',

            'large_package_price.required' => 'Large package price is required',
            'large_package_price.numeric' => 'Large package price must be a number',
            'large_package_price.min' => 'Large package price must be greater than 0'
        ];
    }
}
