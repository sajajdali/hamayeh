<?php

namespace App\Http\Requests;

use App\Support\NormalizeIranianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBloggerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => app(NormalizeIranianPhone::class)->handle($this->string('phone')->toString())]);
        }
    }

    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'regex:/^[a-z0-9]{4}$/', Rule::unique('bloggers', 'code')],
            'slug' => ['required', 'string', 'regex:/^[a-z0-9_-]{2,24}$/', Rule::unique('bloggers', 'slug')],
            'phone' => ['nullable', 'regex:/^09\d{9}$/'],
            'password' => ['nullable', 'string', 'min:4', 'max:255'],
        ];
    }
}
