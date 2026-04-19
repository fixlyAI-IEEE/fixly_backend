<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone'    => ['required', 'string', 'exists:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}