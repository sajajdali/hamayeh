<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

it('shows Persian validation errors when the panel login fields are empty', function () {
    Livewire::test('pages::panel-login')
        ->call('login')
        ->assertHasErrors(['username' => 'required', 'password' => 'required'])
        ->assertSee('نام کاربری را وارد کنید.')
        ->assertSee('رمز عبور را وارد کنید.');
});

it('shows a Persian error for invalid panel credentials', function () {
    Livewire::test('pages::panel-login')
        ->set('username', 'invalid-user')
        ->set('password', 'invalid-password')
        ->call('login')
        ->assertHasErrors(['login'])
        ->assertSee('نام کاربری یا رمز عبور صحیح نیست.');
});
