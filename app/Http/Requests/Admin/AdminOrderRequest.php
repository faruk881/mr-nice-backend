<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminOrderRequest extends FormRequest
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
            'per_page'   => 'integer|min:1|max:100',
            'status'     => 'nullable|in:pending,accepted,pickedup,delivered,cancelled',
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'search'     => 'nullable|string|max:255',
        ];
    }

    public function message(): array
    {
        return [
            'status.in'            => 'The status must be one of: pending, accepted, pickedup, delivered, or cancelled.',
            'start_date.date'      => 'The start date must be a valid date format (YYYY-MM-DD).',
            'start_date.before_or_equal' => 'The start date cannot be after the end date.',
            'end_date.after_or_equal'    => 'The end date must be a date after or equal to the start date.',
            'per_page.max'         => 'You cannot request more than 100 records per page.',
        ];
    }
}
