<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBloggerAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return ['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']];
    }
}
