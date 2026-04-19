<?php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // User fields
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,phone'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers()
            ],

            // Worker-specific fields
            'job_type_id' => ['required', 'exists:job_types,id'],

            // optional user profile fields
            'city'  => ['nullable', 'string', 'max:100'],
            'areas' => ['nullable', 'string', 'max:255'],
            'working_days' => ['required', 'array'],
            'working_days.*' => ['string'],
            'profile_picture'  => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'avg_price'        => ['nullable', 'numeric', 'min:0'],
        ];
    }
}