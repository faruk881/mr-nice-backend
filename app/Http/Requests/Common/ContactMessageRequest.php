<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactMessageRequest extends FormRequest
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
            'topic' => ['required', 'string', 'max:100'],
            'order_number' => ['nullable',
            Rule::exists('orders', 'order_number')->where(function ($query) {
                $query->where('user_id', auth()->id());
            }),
        ],
            'message' => ['required', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'topic.required' => 'Please select a topic.',
            'topic.string' => 'Topic must be a valid text.',
            'topic.max' => 'Topic cannot exceed 100 characters.',

            'order_number.exists' => 'This order number does not exist or does not belong to you.',

            'message.required' => 'Please write your message.',
            'message.string' => 'Message must be valid text.',
            'message.min' => 'Message must be at least 10 characters long.',
        ];
    }
}
