<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return [
            'result' => ['required', 'string', Rule::in([
                'پاسخ داد — تأیید کرد',
                'پاسخ داد — بعداً تماس بگیریم',
                'پاسخ نداد',
                'خارج از دسترس',
                'شماره اشتباه',
                'منصرف شد',
            ])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
