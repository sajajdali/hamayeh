<?php

use App\Models\Blogger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('renders an active blogger landing page at its short code', function () {
    $blogger = Blogger::factory()->create(['code' => 't525']);

    $response = $this->get('/s/'.$blogger->code)->assertOk()->assertSee($blogger->name);
    $html = $response->getContent();

    expect(strpos($html, 'build/assets/app-'))->toBeLessThan(strpos($html, '/design/support.js'));
});

it('returns 404 for an inactive blogger short link', function () {
    $blogger = Blogger::factory()->create(['code' => 't526', 'is_active' => false]);

    $this->get('/s/'.$blogger->code)->assertNotFound();
});
