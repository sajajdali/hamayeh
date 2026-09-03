<?php

use App\Models\OtpCode;
use App\Notifications\OtpCodeNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

it('requests an OTP for a valid Iranian mobile number', function () {
    Notification::fake();

    $this->postJson(route('otp.store'), ['phone' => '09121234567'])
        ->assertOk()
        ->assertJsonPath('phone', '09121234567');

    $this->assertDatabaseHas('otp_codes', ['phone' => '09121234567']);
    Notification::assertSentOnDemand(OtpCodeNotification::class);
});

it('verifies the OTP and marks it as consumed', function () {
    OtpCode::query()->create([
        'phone' => '09121234567',
        'code_hash' => Hash::make('4821'),
        'expires_at' => now()->addMinutes(2),
        'ip' => '127.0.0.1',
    ]);

    $this->postJson(route('otp.verify'), ['phone' => '۰۹۱۲۱۲۳۴۵۶۷', 'code' => '۴۸۲۱'])
        ->assertNoContent()
        ->assertCookie('event_verified_phone', '09121234567');

    expect(OtpCode::query()->sole()->consumed_at)->not->toBeNull();
});

it('does not send SMS and accepts 1234 in the SMS sandbox', function () {
    config()->set('shsms.sandbox', true);
    Notification::fake();

    $this->postJson(route('otp.store'), ['phone' => '09121234567'])
        ->assertOk();

    Notification::assertNothingSent();

    $this->postJson(route('otp.verify'), ['phone' => '09121234567', 'code' => '1234'])
        ->assertNoContent();

    expect(OtpCode::query()->sole()->consumed_at)->not->toBeNull();
});
