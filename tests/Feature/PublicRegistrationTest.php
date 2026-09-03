<?php

use App\Jobs\SendSmsMessage;
use App\Models\Blogger;
use App\Models\SmsMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

function registrationPayload(): array
{
    return [
        'full_name' => 'زهرا محمدی',
        'grade' => '12',
        'field' => 'science',
        'school' => 'فرزانگان',
        'gpa' => '18.50',
        'study_city' => 'تهران',
        'father_job' => 'کارمند',
        'province' => 'تهران',
        'city' => 'تهران',
        'area' => 'سعادت‌آباد',
        'guardian_name' => 'رضا محمدی',
        'guardian_phone' => '09121234567',
    ];
}

it('keeps a verified visitor signed in and creates their registration', function () {
    $blogger = Blogger::factory()->create();
    Queue::fake([SendSmsMessage::class]);

    $response = $this->withSession(['otp_verified_phone' => '09121111111'])
        ->postJson(route('landing.registrations.store', $blogger), registrationPayload())
        ->assertCreated()
        ->assertJsonPath('ticket_code', $blogger->code.'-1');

    $this->assertDatabaseHas('registrations', ['phone' => '09121111111', 'ticket_code' => $response->json('ticket_code')]);
    $this->assertDatabaseHas('sms_messages', [
        'to' => '09121111111',
        'body' => "سلام زهرا محمدی عزیز\nعضویت شما انجام شد و بلیط برای شما صادر شده و برای تایید نهایی با شما تماس گرفته خواهد شد",
    ]);

    $message = SmsMessage::query()->sole();
    expect($message->parameters)->toBe(['زهرا محمدی']);
    Queue::assertPushed(SendSmsMessage::class, function (SendSmsMessage $job): bool {
        return $job->smsMessageId === SmsMessage::query()->sole()->id
            && $job->actorType === null
            && $job->actorId === null;
    });

    $this->withSession(['otp_verified_phone' => '09121111111'])
        ->getJson(route('landing.registration-state', $blogger))
        ->assertOk()
        ->assertJsonPath('registered', true)
        ->assertJsonPath('ticket_code', $response->json('ticket_code'));

    $this->withSession(['otp_verified_phone' => '09121111111'])
        ->get(route('landing.ticket', $blogger))
        ->assertOk()
        ->assertSee($response->json('ticket_code'));
});

it('does not let an unverified visitor create a registration', function () {
    $blogger = Blogger::factory()->create();

    $this->postJson(route('landing.registrations.store', $blogger), registrationPayload())
        ->assertForbidden();
});

it('returns a Persian validation message when the grade point average is missing', function () {
    $blogger = Blogger::factory()->create();
    $payload = registrationPayload();
    unset($payload['gpa']);

    $this->withSession(['otp_verified_phone' => '09121111111'])
        ->postJson(route('landing.registrations.store', $blogger), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['gpa'])
        ->assertJsonPath('errors.gpa.0', 'وارد کردن معدل الزامی است.');
});

it('keeps the registration form behind OTP verification in the landing interface', function () {
    $blogger = Blogger::factory()->create();

    $this->get(route('landing', $blogger))
        ->assertOk()
        ->assertSee("form: { studyCity: 'تهران', province: 'تهران', city: 'تهران' }", false)
        ->assertSee('const toLatinDigits =', false)
        ->assertSee("if (this.state.step === 'otp')", false)
        ->assertSee("if (this.state.step === 'done')", false)
        ->assertSee("this.state.step === 'done' ? 'تکمیل فرم ثبت‌نام'", false);
});

it('normalizes Persian digits in a guardian mobile number before saving', function () {
    $blogger = Blogger::factory()->create();
    $payload = registrationPayload();
    $payload['guardian_phone'] = '۰۹۱۲۱۲۳۴۵۶۷';

    $this->withSession(['otp_verified_phone' => '09121111111'])
        ->postJson(route('landing.registrations.store', $blogger), $payload)
        ->assertCreated();

    $this->assertDatabaseHas('registrations', ['guardian_phone' => '09121234567']);
});
