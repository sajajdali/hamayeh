<?php

namespace App\Actions;

use App\Enums\SmsStatus;
use App\Jobs\SendSmsMessage;
use App\Models\Registration;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\DB;

class QueueRegistrationConfirmationSms
{
    private const Body = "سلام {نام} عزیز\nعضویت شما انجام شد و بلیط برای شما صادر شده و برای تایید نهایی با شما تماس گرفته خواهد شد";

    public function handle(Registration $registration): SmsMessage
    {
        $message = SmsMessage::query()->create([
            'registration_id' => $registration->id,
            'to' => $registration->phone,
            'body' => strtr(self::Body, ['{نام}' => $registration->full_name]),
            'provider_template' => (string) DB::table('settings')->where('key', 'shsms_template')->value('value'),
            'parameters' => [$registration->full_name],
            'status' => SmsStatus::Queued,
        ]);

        SendSmsMessage::dispatch($message->id)->onQueue('sms');

        return $message;
    }
}
