<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PayoutThrseholdsRequest extends FormRequest
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
            'minimum_amount' => ['required', 'numeric', 'gt:0'],
            'maximum_amount' => ['required', 'numeric', 'gt:minimum_amount'],
        ];
    }

    public function messages(): array
    
    {
        return [
            'minimum_amount.required' => 'Minimum amount is required.',
            'minimum_amount.numeric'  => 'Minimum amount must be a number.',
            'minimum_amount.gt'       => 'Minimum amount must be greater than 0.',

            'maximum_amount.required' => 'Maximum amount is required.',
            'maximum_amount.numeric'  => 'Maximum amount must be a number.',
            'maximum_amount.gt'       => 'Maximum amount must be greater than the minimum amount.',
        ];
    }
}
