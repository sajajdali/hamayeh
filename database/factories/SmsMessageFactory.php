<?php

namespace Database\Factories;

use App\Enums\SmsStatus;
use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SmsMessage> */
class SmsMessageFactory extends Factory
{
    public function definition(): array
    {
        return ['to' => '09121234567', 'body' => 'پیام آزمایشی', 'status' => SmsStatus::Queued];
    }
}
