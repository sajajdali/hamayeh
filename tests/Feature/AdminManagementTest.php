<?php

use App\Enums\ActivityType;
use App\Jobs\ExportRegistrationsExcel;
use App\Models\ActivityLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(LazilyRefreshDatabase::class);

it('allows a super admin to create a manager', function () {
    $super = User::factory()->super()->create();

    $this->actingAs($super)
        ->postJson(route('panel.admins.store'), [
            'name' => 'مدیر میانی',
            'username' => 'manager.one',
            'password' => 'secret123',
            'role' => 'mid',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('users', ['username' => 'manager.one', 'role' => 'mid']);
});

it('forbids a mid-level manager from creating another manager', function () {
    $manager = User::factory()->create(['role' => 'mid']);

    $this->actingAs($manager)
        ->postJson(route('panel.admins.store'), [
            'name' => 'مدیر جدید',
            'username' => 'another.manager',
            'password' => 'secret123',
            'role' => 'mid',
        ])
        ->assertForbidden();
});

it('does not let a manager delete or deactivate their own account', function () {
    $super = User::factory()->super()->create();

    $this->actingAs($super)
        ->deleteJson(route('panel.admins.destroy', $super))
        ->assertUnprocessable();

    $this->actingAs($super)
        ->patchJson(route('panel.admins.toggle', $super))
        ->assertUnprocessable();
});

it('exports activity logs as utf8 csv with a Persian header and bom', function () {
    $manager = User::factory()->create();
    $registration = Registration::factory()->create();
    ActivityLog::query()->create([
        'registration_id' => $registration->id,
        'actor_type' => User::class,
        'actor_id' => $manager->id,
        'type' => ActivityType::Status,
        'body' => 'وضعیت ثبت‌نام تغییر کرد.',
    ]);

    $response = $this->actingAs($manager)->get(route('panel.activity.export'));

    $response->assertOk();
    expect($response->streamedContent())
        ->toStartWith("\xEF\xBB\xBF")
        ->toContain('زمان,نوع,مدیر')
        ->toContain('کد بلیط')
        ->toContain($registration->ticket_code);
});

it('queues an Excel export of registrations', function () {
    Bus::fake();
    $manager = User::factory()->create();

    $this->actingAs($manager)
        ->postJson(route('panel.registrations.export.excel'))
        ->assertAccepted()
        ->assertJsonPath('path', fn (string $path): bool => str_starts_with($path, 'exports/registrations-') && str_ends_with($path, '.xlsx'));

    Bus::assertDispatched(ExportRegistrationsExcel::class);
});
