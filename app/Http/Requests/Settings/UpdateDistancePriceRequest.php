<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDistancePriceRequest extends FormRequest
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
            'price_per_km' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'price_per_km.required' => 'Price per km is required',
            'price_per_km.numeric' => 'Price per km must be a number',
            'price_per_km.min' => 'Price per km must be greater than 0'
        ];
    }
}
