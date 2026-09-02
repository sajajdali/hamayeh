<?php

namespace App\Broadcasting;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $data = $notification->toArray($notifiable);
        Http::withToken((string) config('shsms.api_token'))->timeout(10)->get((string) config('shsms.endpoint'), ['receptor' => $data['receptor'], 'template' => $data['template'], 'param' => $data['params']]);
    }
}
