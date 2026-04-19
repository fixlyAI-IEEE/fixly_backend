<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'job_type_id' => ['required', 'integer', 'exists:job_types,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'city'        => ['nullable', 'string', 'max:100'],
        ];
    }
}