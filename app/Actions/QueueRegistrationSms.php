<?php

namespace App\Actions;

use App\Enums\SmsStatus;
use App\Jobs\SendSmsMessage;
use App\Models\Registration;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;

class QueueRegistrationSms
{
    public function __construct(private RenderSmsTemplate $renderSmsTemplate) {}

    public function handle(Registration $registration, SmsTemplate $template, User $actor, string $recipient): SmsMessage
    {
        $phone = $recipient === 'guardian' ? $registration->guardian_phone : $registration->phone;
        $message = SmsMessage::query()->create([
            'registration_id' => $registration->id,
            'sms_template_id' => $template->id,
            'to' => $phone,
            'body' => $this->renderSmsTemplate->handle($template, $registration),
            'status' => SmsStatus::Queued,
        ]);

        SendSmsMessage::dispatch($message->id, $actor->getMorphClass(), $actor->getKey())->onQueue('sms');

        return $message;
    }
}
