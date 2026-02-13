<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerBecomeCourierRequest extends FormRequest
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
            'city'        => 'required|string|max:255',
            'vehicle_type'=> 'required|string|max:100',
            'package_size'=> 'nullable|string|max:50',
            'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096', // example: allow image/pdf upload
        ];
    }

    public function messages(): array
    {
        return [
            'city.required'         => 'Please select your city.',
            'vehicle_type.required' => 'Please select the type of vehicle you will use.',
            'id_document.required'  => 'Please upload a valid ID document.',
            'id_document.file'      => 'ID document must be a file.',
            'id_document.mimes'     => 'ID document must be a JPG, PNG, or PDF file.',
            'id_document.max'       => 'ID document cannot be larger than 4MB.',
        ];
    }
}
