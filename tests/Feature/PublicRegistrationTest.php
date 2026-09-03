<?php

use App\Models\Blogger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

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

    $response = $this->withSession(['otp_verified_phone' => '09121111111'])
        ->postJson(route('landing.registrations.store', $blogger), registrationPayload())
        ->assertCreated()
        ->assertJsonPath('ticket_code', $blogger->code.'-1');

    $this->assertDatabaseHas('registrations', ['phone' => '09121111111', 'ticket_code' => $response->json('ticket_code')]);

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

it('normalizes Persian digits in a guardian mobile number before saving', function () {
    $blogger = Blogger::factory()->create();
    $payload = registrationPayload();
    $payload['guardian_phone'] = '۰۹۱۲۱۲۳۴۵۶۷';

    $this->withSession(['otp_verified_phone' => '09121111111'])
        ->postJson(route('landing.registrations.store', $blogger), $payload)
        ->assertCreated();

    $this->assertDatabaseHas('registrations', ['guardian_phone' => '09121234567']);
});
