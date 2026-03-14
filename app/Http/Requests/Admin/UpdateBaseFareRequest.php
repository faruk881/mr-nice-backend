<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBaseFareRequest extends FormRequest
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
            'base_fare' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'base_fare.required' => 'Base fare is required',
            'base_fare.numeric' => 'Base fare must be a number',
            'base_fare.min' => 'Base fare must be greater than 0'
        ];
    }
}
