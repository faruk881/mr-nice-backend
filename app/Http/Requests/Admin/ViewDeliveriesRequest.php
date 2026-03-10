<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ViewDeliveriesRequest extends FormRequest
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
            'per_page' => 'nullable|integer|min:1|max:100',
            'filter' => 'nullable|string|in:pending_payment,pending,accepted,pickedup,pending_delivery,delivered,cancelled,all',
            'search' => 'nullable|string|max:255',
            'sort'  => 'nullable|string|in:created_at,updated_at|required_with:order',
            'order' => 'nullable|string|in:asc,desc|required_with:sort',
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'The per page value must be a number.',
            'per_page.min'     => 'The per page value must be at least 1.',
            'filter.in'        => 'The selected filter is invalid. muse be one of the following: pending_payment, pending, accepted, pickedup, pending_delivery, delivered, cancelled, all',
            'search.string'    => 'The search value must be text.',
            'search.max'       => 'The search text may not exceed 255 characters.',
            'sort.in'          => 'The sort field is invalid. must be one of the following: created_at, updated_at',
            'order.in'         => 'The order must be either asc or desc. must be one of the following: asc, desc',
            'sort.required_with' => 'The sort field is required when order is present',
            'order.required_with' => 'The order field is required when sort is present',
        ];
    }
}
