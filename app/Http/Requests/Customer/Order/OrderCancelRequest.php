<?php

namespace App\Http\Requests\Customer\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderCancelRequest extends FormRequest
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
            'cancel_reason' => 'required|string|min:5|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Please provide a reason for cancelling the order.',
            'cancel_reason.string'   => 'The cancellation reason must be valid text.',
            'cancel_reason.min'      => 'The cancellation reason must be at least :min characters.',
            'cancel_reason.max'      => 'The cancellation reason may not exceed :max characters.',
        ];
    }
}
