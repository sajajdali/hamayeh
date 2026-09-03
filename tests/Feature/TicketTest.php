<?php

use App\Models\Registration;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

uses(LazilyRefreshDatabase::class);

it('rejects an unsigned ticket link', function () {
    $registration = Registration::factory()->create();

    $this->get('/ticket/'.$registration->ticket_code)->assertForbidden();
});

it('renders a signed ticket link', function () {
    $registration = Registration::factory()->create();
    DB::table('settings')->insert([
        'key' => 'event_starts_at',
        'value' => CarbonImmutable::create(2028, 9, 21, 9, 0, 0, 'Asia/Tehran')->getTimestamp(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $url = URL::temporarySignedRoute('ticket.show', now()->addDay(), ['registration' => $registration]);

    $this->get($url)
        ->assertOk()
        ->assertSee($registration->ticket_code)
        ->assertSee('بازگشت به صفحه اصلی')
        ->assertSee('دانلود بلیط')
        ->assertSee('۳۱ شهریور ۱۴۰۷');
});
