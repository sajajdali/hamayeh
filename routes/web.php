<?php

use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ReferenceDesignController;
use App\Http\Controllers\RegistrationCallController;
use App\Http\Controllers\RegistrationStatusController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::panel-login')->name('home');
Route::livewire('/panel/login', 'pages::panel-login')->name('panel.login');
Route::post('/panel/logout', LogoutController::class)->name('panel.logout');
Route::get('/panel', [ReferenceDesignController::class, 'admin'])->middleware('auth.panel')->name('panel.registrations');
Route::get('/panel/admins', [ReferenceDesignController::class, 'admin'])->middleware(['auth.panel', 'super'])->name('panel.admins');
Route::post('/panel/r/{registration:ticket_code}/calls', RegistrationCallController::class)->middleware(['auth.panel', 'staff'])->name('panel.registration.calls');
Route::put('/panel/r/{registration:ticket_code}/status', RegistrationStatusController::class)->middleware(['auth.panel', 'staff'])->name('panel.registration.status');
Route::get('/s/{blogger:code}', [ReferenceDesignController::class, 'landing'])->name('landing');
Route::get('/ticket/{registration:ticket_code}', [ReferenceDesignController::class, 'ticket'])->middleware('signed')->name('ticket.show');
Route::get('/design/support.js', [ReferenceDesignController::class, 'support'])->name('design.support');
Route::get('/design/assets/{asset}', [ReferenceDesignController::class, 'asset'])->where('asset', '[A-Za-z0-9._-]+')->name('design.assets');
