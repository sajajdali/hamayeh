<?php

namespace App\Notifications;

use App\Broadcasting\LogChannel;
use App\Broadcasting\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class OtpCodeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $code, public string $phone) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [config('shsms.sandbox') ? LogChannel::class : SmsChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'template' => DB::table('settings')->where('key', 'shsms_otp_template')->value('value') ?: config('shsms.templates.login_webapp'),
            'receptor' => $this->phone,
            'params' => [$this->code],
        ];
    }
}
