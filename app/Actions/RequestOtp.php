<?php

namespace App\Actions;

use App\Models\OtpCode;
use App\Notifications\OtpCodeNotification;
use App\Support\NormalizeIranianPhone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RequestOtp
{
    public function handle(string $phone, string $ip): string
    {
        $phone = app(NormalizeIranianPhone::class)->handle($phone);
        if (! preg_match('/^09\d{9}$/', $phone) || RateLimiter::tooManyAttempts('otp:phone:'.$phone, 3) || RateLimiter::tooManyAttempts('otp:ip:'.$ip, 10)) {
            throw ValidationException::withMessages(['phone' => __('event.otp_limit')]);
        }
        RateLimiter::hit('otp:phone:'.$phone, 600);
        RateLimiter::hit('otp:ip:'.$ip, 600);
        $code = (string) random_int(1000, 9999);
        OtpCode::create(['phone' => $phone, 'code_hash' => Hash::make($code), 'expires_at' => now()->addSeconds(120), 'ip' => $ip]);
        Notification::route('sms', $phone)->notify(new OtpCodeNotification($code, $phone));

        return $phone;
    }
}
