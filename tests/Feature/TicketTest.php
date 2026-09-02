<?php

use App\Models\Registration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

it('rejects an unsigned ticket link', function () {
    $registration = Registration::factory()->create();

    $this->get('/ticket/'.$registration->ticket_code)->assertForbidden();
});

it('renders a signed ticket link', function () {
    $registration = Registration::factory()->create();
    $url = URL::temporarySignedRoute('ticket.show', now()->addDay(), ['registration' => $registration]);

    $this->get($url)->assertOk()->assertSee($registration->ticket_code);
});
