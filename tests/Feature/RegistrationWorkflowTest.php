<?php

use App\Enums\ActivityType;
use App\Enums\RegistrationStatus;
use App\Models\Blogger;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('records a successful call, approves the registration, and writes activity logs', function () {
    $manager = User::factory()->create();
    $registration = Registration::factory()->create(['status' => RegistrationStatus::Pending]);

    $this->actingAs($manager)
        ->postJson(route('panel.registration.calls', $registration), [
            'result' => 'پاسخ داد — تأیید کرد',
            'note' => 'برای حضور تأیید کرد.',
        ])
        ->assertOk();

    expect($registration->refresh()->status)->toBe(RegistrationStatus::Approved);
    expect($registration->activityLogs()->where('type', ActivityType::Call->value)->value('body'))->toContain('برای حضور تأیید کرد.');
    expect($registration->activityLogs()->where('type', ActivityType::Status->value)->exists())->toBeTrue();
});

it('records a contact result without reopening a finalized registration', function () {
    $manager = User::factory()->create();
    $registration = Registration::factory()->create(['status' => RegistrationStatus::Approved]);

    $this->actingAs($manager)
        ->postJson(route('panel.registration.calls', $registration), [
            'result' => 'پاسخ نداد',
        ])
        ->assertOk();

    expect($registration->refresh()->status)->toBe(RegistrationStatus::Approved);
    expect($registration->activityLogs()->where('type', ActivityType::Call->value)->value('body'))->toBe('نتیجه تماس: پاسخ نداد');
});

it('allows returning finalized registrations to follow-up', function (RegistrationStatus $initialStatus) {
    $manager = User::factory()->create();
    $registration = Registration::factory()->create(['status' => $initialStatus]);

    $this->actingAs($manager)
        ->putJson(route('panel.registration.status', $registration), ['status' => 'calling'])
        ->assertOk();

    expect($registration->refresh()->status)->toBe(RegistrationStatus::Calling);
})->with([RegistrationStatus::Approved, RegistrationStatus::Canceled]);

it('rejects an invalid status transition', function () {
    $manager = User::factory()->create();
    $registration = Registration::factory()->create(['status' => RegistrationStatus::Approved]);

    $this->actingAs($manager)
        ->putJson(route('panel.registration.status', $registration), ['status' => 'pending'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('forbids a blogger from recording calls for a registration', function () {
    $blogger = Blogger::factory()->create();
    $registration = Registration::factory()->for($blogger)->create();

    $this->actingAs($blogger, 'blogger')
        ->postJson(route('panel.registration.calls', $registration), [
            'result' => 'پاسخ نداد',
        ])
        ->assertForbidden();
});
