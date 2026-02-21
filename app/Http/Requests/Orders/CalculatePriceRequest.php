<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CalculatePriceRequest extends FormRequest
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
            'pickup_address'     => 'required|string|max:255',
            'pickup_notes'       => 'nullable|string|max:500',
            'pickup_lat'         => 'required|numeric|between:-90,90',
            'pickup_lon'         => 'required|numeric|between:-180,180',
            'delivery_address'   => 'required|string|max:255',
            'delivery_lat'       => 'required|numeric|between:-90,90',
            'delivery_lon'       => 'required|numeric|between:-180,180',
            'delivery_notes'     => 'nullable|string|max:500',
            'items'              => 'required|string|max:1000',
            'package_size'       => 'required|in:small,medium,large',
            'additional_notes'   => 'nullable|string|max:500',
            'booking_date'       => 'required|date|after_or_equal:'.now()->startOfDay()->toDateTimeString(),
        ];
    }
}
