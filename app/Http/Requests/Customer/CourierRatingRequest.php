<?php

namespace App\Http\Requests\Customer;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class CourierRatingRequest extends FormRequest
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
            'order_id' => 'required',
            'rating' => 'required|integer|between:1,5'
        ];
    }

    public function message(): array
    {
        return [
            'order_id.required' => 'Order id is required.',
            'rating.required' => 'Rating is required.',
            'rating.between' => 'Rating must be between 1 and 5.'
        ];
    
    }
}
