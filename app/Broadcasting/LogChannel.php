<?php

namespace App\Broadcasting;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LogChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        Log::info('SMS sandbox', $notification->toArray($notifiable));
    }
}
