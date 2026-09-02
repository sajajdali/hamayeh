<?php

use App\Models\Blogger;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('returns only a blogger’s registrations from the visibility scope', function () {
    $blogger = Blogger::factory()->create();
    $otherBlogger = Blogger::factory()->create();
    $ownedRegistration = Registration::factory()->for($blogger)->create();
    Registration::factory()->for($otherBlogger)->create();

    $visibleRegistrationIds = Registration::query()->visibleTo($blogger)->pluck('id');

    expect($visibleRegistrationIds->all())->toBe([$ownedRegistration->id]);
});

it('does not restrict staff registration visibility', function () {
    Registration::factory()->count(2)->create();
    $staffMember = User::factory()->create();

    expect(Registration::query()->visibleTo($staffMember)->count())->toBe(2);
});
