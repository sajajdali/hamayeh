<?php

namespace App\Actions;

use App\Models\Registration;
use App\Models\SmsTemplate;

class RenderSmsTemplate
{
    public function handle(SmsTemplate $template, Registration $registration): string
    {
        return strtr($template->body, [
            '{نام}' => $registration->full_name,
            '{کد}' => $registration->ticket_code,
            '{تاریخ}' => __('event.date'),
            '{همراه}' => $registration->guardian_name,
            '{پایه}' => $registration->grade->label(),
            '{رشته}' => $registration->field->label(),
        ]);
    }
}
