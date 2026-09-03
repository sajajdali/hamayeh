<?php

use App\Models\Blogger;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

it('renders the three dedicated portal login pages', function (string $path, string $title) {
    $this->get($path)
        ->assertOk()
        ->assertSee($title);
})->with([
    ['/admin', 'ورود مدیریت'],
    ['/blogger', 'ورود بلاگرها'],
    ['/sales_manager', 'ورودی مدیر'],
]);

it('authenticates a super manager from the management portal', function () {
    $manager = User::factory()->super()->create(['username' => 'super.manager']);

    Livewire::test('pages::portal-login')
        ->set('username', $manager->username)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('panel.registrations'));

    $this->assertAuthenticatedAs($manager);
});

it('authenticates a mid-level manager only from the sales manager portal', function () {
    $manager = User::factory()->create(['username' => 'sales.manager']);

    Livewire::test('pages::portal-login', ['portal' => 'sales_manager'])
        ->set('username', $manager->username)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('panel.registrations'));

    $this->assertAuthenticatedAs($manager);
});

it('authenticates a blogger only from the blogger portal', function () {
    $blogger = Blogger::factory()->create(['slug' => 'test-blogger']);

    Livewire::test('pages::portal-login', ['portal' => 'blogger'])
        ->set('username', $blogger->slug)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('panel.registrations'));

    $this->assertAuthenticatedAs($blogger, 'blogger');
});

it('authenticates a blogger from the blogger portal with their mobile number', function () {
    $blogger = Blogger::factory()->create([
        'phone' => '09252588888',
        'password' => '09252588888',
    ]);

    Livewire::test('pages::portal-login', ['portal' => 'blogger'])
        ->set('username', '09252588888')
        ->set('password', '09252588888')
        ->call('login')
        ->assertRedirect(route('panel.registrations'));

    $this->assertAuthenticatedAs($blogger, 'blogger');
});

it('ends an existing manager session before authenticating a blogger', function () {
    $manager = User::factory()->super()->create();
    $blogger = Blogger::factory()->create([
        'phone' => '09252588888',
        'password' => '09252588888',
    ]);

    $this->actingAs($manager);

    Livewire::test('pages::portal-login', ['portal' => 'blogger'])
        ->set('username', '09252588888')
        ->set('password', '09252588888')
        ->call('login')
        ->assertRedirect(route('panel.registrations'));

    $this->assertGuest('web');
    $this->assertAuthenticatedAs($blogger, 'blogger');
});

it('does not let a mid-level manager use the management portal', function () {
    $manager = User::factory()->create(['username' => 'mid.manager']);

    Livewire::test('pages::portal-login')
        ->set('username', $manager->username)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['login']);
});

it('uses the default blogger for registrations opened from the home page', function () {
    Queue::fake();

    $defaultBlogger = Blogger::query()->where('code', 'a0')->sole();

    $this->get('/')->assertOk();

    $this->withSession(['otp_verified_phone' => '09121234567'])
        ->postJson(route('landing.registrations.store', $defaultBlogger), [
            'full_name' => 'کاربر پیش‌فرض',
            'grade' => '12',
            'field' => 'math',
            'school' => 'دبیرستان نمونه',
            'gpa' => 19.25,
            'study_city' => 'تهران',
            'father_job' => 'آزاد',
            'province' => 'تهران',
            'city' => 'تهران',
            'area' => 'منطقه 1',
            'guardian_name' => 'ولی دانش‌آموز',
            'guardian_phone' => '09129876543',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('bloggers', [
        'code' => 'a0',
        'slug' => 'default',
        'name' => 'ثبت‌نام پیش‌فرض',
    ]);

    $this->assertDatabaseHas('registrations', [
        'blogger_id' => $defaultBlogger->id,
        'phone' => '09121234567',
    ]);
});
