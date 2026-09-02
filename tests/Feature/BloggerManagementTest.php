<?php

use App\Models\Blogger;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

it('creates a blogger with a four-character referral code', function () {
    $manager = User::factory()->create();

    $this->actingAs($manager)
        ->postJson(route('panel.bloggers.store'), [
            'name' => 'بلاگر تست',
            'code' => 't525',
            'slug' => 'testblogger',
            'phone' => '09121234567',
            'password' => 'secret123',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('bloggers', ['code' => 't525', 'slug' => 'testblogger']);
});

it('rejects referral codes that are not exactly four characters', function () {
    $manager = User::factory()->create();

    $this->actingAs($manager)
        ->postJson(route('panel.bloggers.store'), [
            'name' => 'بلاگر تست',
            'code' => 'a10',
            'slug' => 'testblogger',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('soft deletes a blogger and preserves their registrations without a referrer', function () {
    $super = User::factory()->super()->create();
    $blogger = Blogger::factory()->create(['code' => 't525']);
    $registration = Registration::factory()->for($blogger)->create();

    $this->actingAs($super)
        ->deleteJson(route('panel.bloggers.destroy', $blogger))
        ->assertNoContent();

    expect($registration->refresh()->blogger_id)->toBeNull();
    $this->assertSoftDeleted('bloggers', ['id' => $blogger->id]);
});

it('stores a validated blogger avatar', function () {
    Storage::fake('public');
    $manager = User::factory()->create();
    $blogger = Blogger::factory()->create(['code' => 't525']);

    $this->actingAs($manager)
        ->post(route('panel.bloggers.avatar', $blogger), ['avatar' => UploadedFile::fake()->image('avatar.png')])
        ->assertOk();

    Storage::disk('public')->assertExists($blogger->refresh()->avatar_path);
});
