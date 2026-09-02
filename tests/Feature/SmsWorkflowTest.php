<?php

use App\Enums\ActivityType;
use App\Enums\SmsStatus;
use App\Jobs\SendSmsMessage;
use App\Models\Registration;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
