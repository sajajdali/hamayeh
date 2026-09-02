<?php

use App\Models\Blogger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('renders an active blogger landing page at its short code', function () {
    $blogger = Blogger::factory()->create(['code' => 't525']);

    $this->get('/s/'.$blogger->code)->assertOk()->assertSee($blogger->name);
});

it('returns 404 for an inactive blogger short link', function () {
    $blogger = Blogger::factory()->create(['code' => 't526', 'is_active' => false]);

    $this->get('/s/'.$blogger->code)->assertNotFound();
});
