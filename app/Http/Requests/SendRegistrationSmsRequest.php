<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendRegistrationSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        return ['template_id' => ['required', 'integer', 'exists:sms_templates,id'], 'recipient' => ['required', Rule::in(['student', 'guardian'])]];
    }
}
