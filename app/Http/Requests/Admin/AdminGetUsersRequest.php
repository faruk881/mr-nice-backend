<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminGetUsersRequest extends FormRequest
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
            'user_type' => 'required|string|in:courier,customer',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:created_at,updated_at',
            'filter' => 'nullable|string|in:active,suspended'
        ];
    }

    public function messages(): array
    {
        return [
            'user_type.required' => 'The user type is required',
            'user_type.string' => 'The user type must be a string',
            'user_type.in' => 'The user type must be either courier or customer',
            'search.string' => 'The search must be a string',
            'per_page.integer' => 'The per page must be an integer',
            'per_page.min' => 'The per page must be at least 1',
            'per_page.max' => 'The per page must be at most 100',
            'sort.string' => 'The sort must be a string',

            'filter.string' => 'The filter must be a string',
            'filter.in' => 'The filter must be either active or suspended'
        ];
    }
}
