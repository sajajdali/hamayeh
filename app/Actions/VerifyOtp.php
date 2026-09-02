<?php

namespace App\Actions;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyOtp
{
    public function handle(string $phone, string $code): void
    {
        $otp = OtpCode::query()->where('phone', $phone)->whereNull('consumed_at')->latest('id')->first();
        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => __('event.otp_expired')]);
        }
        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => __('event.otp_invalid')]);
        }
        $otp->increment('attempts');
        if (! Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages(['code' => __('event.otp_invalid')]);
        }
        $otp->update(['consumed_at' => now()]);
    }
}
