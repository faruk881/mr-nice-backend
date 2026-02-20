<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRequestRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'pickup_address.required'   => 'Pickup address is required.',
            'pickup_address.string'     => 'Pickup address must be a valid text.',
            'pickup_address.max'        => 'Pickup address cannot exceed 255 characters.',
            
            'pickup_notes.string'       => 'Pickup notes must be valid text.',
            'pickup_notes.max'          => 'Pickup notes cannot exceed 500 characters.',
            
            'pickup_lat.required'       => 'Pickup latitude is required.',
            'pickup_lat.numeric'        => 'Pickup latitude must be a number.',
            'pickup_lat.between'        => 'Pickup latitude must be between -90 and 90.',
            
            'pickup_lon.required'       => 'Pickup longitude is required.',
            'pickup_lon.numeric'        => 'Pickup longitude must be a number.',
            'pickup_lon.between'        => 'Pickup longitude must be between -180 and 180.',
            
            'delivery_address.required' => 'Delivery address is required.',
            'delivery_address.string'   => 'Delivery address must be valid text.',
            'delivery_address.max'      => 'Delivery address cannot exceed 255 characters.',
            
            'delivery_lat.required'     => 'Delivery latitude is required.',
            'delivery_lat.numeric'      => 'Delivery latitude must be a number.',
            'delivery_lat.between'      => 'Delivery latitude must be between -90 and 90.',
            
            'delivery_lon.required'     => 'Delivery longitude is required.',
            'delivery_lon.numeric'      => 'Delivery longitude must be a number.',
            'delivery_lon.between'      => 'Delivery longitude must be between -180 and 180.',
            
            'delivery_notes.string'     => 'Delivery notes must be valid text.',
            'delivery_notes.max'        => 'Delivery notes cannot exceed 500 characters.',
            
            'items.required'            => 'Please provide the items you want to deliver.',
            'items.string'              => 'Items must be a valid text.',
            'items.max'                 => 'Items description cannot exceed 1000 characters.',
            
            'package_size.required'     => 'Please select a package size.',
            'package_size.in'           => 'Package size must be small, medium, or large.',
            
            'additional_notes.string'   => 'Additional notes must be valid text.',
            'additional_notes.max'      => 'Additional notes cannot exceed 500 characters.',

            'booking_date.required'     => 'Booking date is required.',
            'booking_date.datetime'     => 'Booking date must be a valid date and time.',
            'booking_date.after_or_equal' => 'Booking date must be after or equal to the current date and time.',
        ];
    }
}
