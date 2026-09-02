<?php

use App\Models\Blogger;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('shows a blogger only their own registrations in the panel', function () {
    $blogger = Blogger::factory()->create();
    $otherBlogger = Blogger::factory()->create();
    $ownedRegistration = Registration::factory()->for($blogger)->create();
    $otherRegistration = Registration::factory()->for($otherBlogger)->create();

    $this->actingAs($blogger, 'blogger')
        ->get(route('panel.registrations'))
        ->assertOk()
        ->assertSee($ownedRegistration->ticket_code)
        ->assertDontSee($otherRegistration->ticket_code);
});

it('forbids a mid-level manager from opening the administrators panel', function () {
    $manager = User::factory()->create(['role' => 'mid']);

    $this->actingAs($manager)
        ->get(route('panel.admins'))
        ->assertForbidden();
});

it('filters registrations in the panel', function () {
    $manager = User::factory()->create();
    $blogger = Blogger::factory()->create();
    $pending = Registration::factory()->count(16)->for($blogger)->create([
        'status' => 'pending',
        'created_at' => now()->subDay(),
    ]);
    $approved = Registration::factory()->for($blogger)->create([
        'status' => 'approved',
        'created_at' => now(),
    ]);

    $this->actingAs($manager)
        ->get(route('panel.registrations', ['status' => 'approved']))
        ->assertOk()
        ->assertSee($approved->ticket_code)
        ->assertDontSee($pending->first()->ticket_code);
});

it('includes a central user-facing error handler for every panel action', function () {
    $manager = User::factory()->create();

    $this->actingAs($manager)
        ->get(route('panel.registrations'))
        ->assertOk()
        ->assertSee('window.panelNotifyError', false)
        ->assertSee('panelBloggerAction', false)
        ->assertSee('panelRegistrationAction', false)
        ->assertSee('panelSmsTemplateAction', false)
        ->assertSee('panelAdminAction', false);
});
