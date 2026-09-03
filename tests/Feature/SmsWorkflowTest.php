<?php

use App\Enums\ActivityType;
use App\Enums\SmsStatus;
use App\Jobs\SendSmsMessage;
use App\Models\Registration;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

it('creates an sms template and queues a rendered registration message', function () {
    Queue::fake();
    $manager = User::factory()->create();
    $registration = Registration::factory()->create();

    $templateId = $this->actingAs($manager)
        ->postJson(route('panel.sms-templates.store'), ['name' => 'بلیط', 'body' => '{نام} عزیز، کد شما {کد} است.'])
        ->assertCreated()
        ->json('id');

    $this->postJson(route('panel.registration.sms', $registration), ['template_id' => $templateId, 'recipient' => 'student'])
        ->assertAccepted();

    $message = SmsMessage::query()->sole();
    expect($message->body)->toContain($registration->full_name)->and($message->status)->toBe(SmsStatus::Queued);
    Queue::assertPushed(SendSmsMessage::class);
});

it('marks a sandbox sms as sent and records an activity log', function () {
    config()->set('shsms.sandbox', true);
    $manager = User::factory()->create();
    $registration = Registration::factory()->create();
    $template = SmsTemplate::factory()->create(['body' => 'سلام {نام}']);
    $message = SmsMessage::factory()->for($registration)->for($template)->create(['status' => SmsStatus::Queued]);

    (new SendSmsMessage($message->id, $manager->getMorphClass(), $manager->id))->handle();

    expect($message->refresh()->status)->toBe(SmsStatus::Sent);
    expect($registration->activityLogs()->where('type', ActivityType::Sms->value)->exists())->toBeTrue();
});

it('sends a SHSMS template with generated registration parameters', function () {
    config()->set('shsms.sandbox', false);
    config()->set('shsms.api_token', 'test-token');
    Http::preventStrayRequests();
    Http::fake(['shsms.ir/api/v1/sendms*' => Http::response(['id' => 'shsms-1'])]);

    $manager = User::factory()->create();
    $registration = Registration::factory()->create(['full_name' => 'زهرا محمدی']);
    $template = SmsTemplate::factory()->create(['name' => 'legacy-template']);
    $message = SmsMessage::factory()->for($registration)->for($template)->create(['status' => SmsStatus::Queued]);
    DB::table('settings')->updateOrInsert(
        ['key' => 'shsms_template'],
        ['value' => 'reminder', 'created_at' => now(), 'updated_at' => now()],
    );

    (new SendSmsMessage($message->id, $manager->getMorphClass(), $manager->id))->handle();

    Http::assertSent(function (Request $request) use ($registration): bool {
        return $request->url() === 'https://shsms.ir/api/v1/sendms?receptor='.$registration->phone.'&template=reminder&param%5B0%5D=%D8%B2%D9%87%D8%B1%D8%A7%20%D9%85%D8%AD%D9%85%D8%AF%DB%8C'
            || ($request['template'] === 'reminder' && $request['param'][0] === 'زهرا محمدی');
    });

    expect($message->refresh()->status)->toBe(SmsStatus::Sent);
});

it('sends a registration confirmation with the configured name parameter', function () {
    config()->set('shsms.sandbox', false);
    config()->set('shsms.api_token', 'test-token');
    Http::preventStrayRequests();
    Http::fake(['shsms.ir/api/v1/sendms*' => Http::response(['id' => 'shsms-confirmation'])]);

    $registration = Registration::factory()->create(['full_name' => 'زهرا محمدی']);
    $message = SmsMessage::factory()->for($registration)->create([
        'body' => "سلام زهرا محمدی عزیز\nعضویت شما انجام شد و بلیط برای شما صادر شده و برای تایید نهایی با شما تماس گرفته خواهد شد",
        'provider_template' => 'registration_confirmed',
        'parameters' => ['زهرا محمدی'],
        'status' => SmsStatus::Queued,
    ]);

    (new SendSmsMessage($message->id))->handle();

    Http::assertSent(fn (Request $request): bool => $request['template'] === 'registration_confirmed'
        && $request['param'] === ['زهرا محمدی']);
    expect($message->refresh()->status)->toBe(SmsStatus::Sent);
});

it('queues a template message without requiring a legacy local template', function () {
    Queue::fake();
    $manager = User::factory()->create();
    $registration = Registration::factory()->create();

    $this->actingAs($manager)
        ->postJson(route('panel.registration.sms', $registration), ['recipient' => 'student'])
        ->assertAccepted();

    expect(SmsMessage::query()->sole()->sms_template_id)->toBeNull();
    Queue::assertPushed(SendSmsMessage::class);
});

it('uses the configured OTP template with only the login code parameter', function () {
    DB::table('settings')->updateOrInsert(
        ['key' => 'shsms_otp_template'],
        ['value' => 'login_code', 'created_at' => now(), 'updated_at' => now()],
    );

    $payload = (new OtpCodeNotification('4821', '09121234567'))->toArray(new stdClass);

    expect($payload)->toBe([
        'template' => 'login_code',
        'receptor' => '09121234567',
        'params' => ['4821'],
    ]);
});
