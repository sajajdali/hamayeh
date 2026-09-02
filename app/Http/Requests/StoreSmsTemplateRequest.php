<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'min:10', 'max:2000']];
    }
}
