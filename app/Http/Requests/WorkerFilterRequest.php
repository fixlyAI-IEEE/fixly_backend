<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkerFilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'job_type_id' => ['nullable', 'integer', 'exists:job_types,id'],
            'city'        => ['nullable', 'string', 'max:100'],
            'areas'       => ['nullable', 'string', 'max:255'],
            'min_rating'  => ['nullable', 'numeric', 'min:0', 'max:5'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}