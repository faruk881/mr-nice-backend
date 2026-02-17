<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'payment_mode' => 'required|in:intent,link'
        ];
    }
    public function messages(): array
    { 
        return[
            'payment_mode.required' => 'Please select a payment mode.',
            'payment_mode.in' => 'Payment mode must be intent or link.',
        ];
    }
}
