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

it('uses the referral code as the short address when the address is omitted', function () {
    $manager = User::factory()->create();

    $this->actingAs($manager)
        ->postJson(route('panel.bloggers.store'), [
            'name' => 'بلاگر تست',
            'code' => 't526',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('bloggers', ['code' => 't526', 'slug' => 't526']);
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
        ->deleteJson(route('panel.bloggers.destroy', $blogger), ['confirm_name' => $blogger->name])
        ->assertNoContent();

    $this->assertSoftDeleted('registrations', ['id' => $registration->id]);
    $this->assertSoftDeleted('bloggers', ['id' => $blogger->id]);
});

it('does not allow the default blogger to be deactivated or deleted', function () {
    $super = User::factory()->super()->create();
    $defaultBlogger = Blogger::query()->where('code', 'a0')->firstOrFail();

    $this->actingAs($super)
        ->patchJson(route('panel.bloggers.toggle', $defaultBlogger))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'بلاگر پیش‌فرض قابل غیرفعال‌سازی نیست.');

    $this->deleteJson(route('panel.bloggers.destroy', $defaultBlogger), ['confirm_name' => $defaultBlogger->name])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'بلاگر پیش‌فرض قابل حذف نیست.');

    $this->assertDatabaseHas('bloggers', ['id' => $defaultBlogger->id, 'is_active' => true]);
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
