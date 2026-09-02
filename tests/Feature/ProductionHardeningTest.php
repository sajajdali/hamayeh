<?php

use App\Models\Blogger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

it('adds baseline security headers to public responses', function () {
    $this->get('/up')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('invalidates the cached blogger landing when blogger data changes', function () {
    Cache::flush();
    $blogger = Blogger::factory()->create(['code' => 't525', 'name' => 'نام نخست']);

    $this->get('/s/t525')->assertOk()->assertSee('نام نخست');
    expect(Cache::has('landing:blogger:'.$blogger->id))->toBeTrue();

    $blogger->update(['name' => 'نام جدید']);

    expect(Cache::has('landing:blogger:'.$blogger->id))->toBeFalse();
    $this->get('/s/t525')->assertOk()->assertSee('نام جدید');
});
