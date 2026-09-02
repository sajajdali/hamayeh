<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]{3,20}$/', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:4', 'max:255'],
            'role' => ['required', Rule::in(['super', 'mid'])],
        ];
    }
}
