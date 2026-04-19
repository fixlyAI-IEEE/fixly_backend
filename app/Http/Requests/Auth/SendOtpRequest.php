<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'exists:users,phone'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.exists' => 'No account found with this phone number.',
        ];
    }
}