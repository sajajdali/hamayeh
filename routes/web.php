<?php

use App\Http\Controllers\ActivityLogExportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BloggerController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ReferenceDesignController;
use App\Http\Controllers\RegistrationCallController;
use App\Http\Controllers\RegistrationSmsController;
use App\Http\Controllers\RegistrationStatusController;
use App\Http\Controllers\SmsTemplateController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::panel-login')->name('home');
Route::livewire('/panel/login', 'pages::panel-login')->name('panel.login');
Route::post('/panel/logout', LogoutController::class)->name('panel.logout');
Route::get('/panel', [ReferenceDesignController::class, 'admin'])->middleware('auth.panel')->name('panel.registrations');
Route::get('/panel/admins', [ReferenceDesignController::class, 'admin'])->middleware(['auth.panel', 'super'])->name('panel.admins');
Route::post('/panel/admins', [AdminController::class, 'store'])->middleware(['auth.panel', 'super'])->name('panel.admins.store');
Route::patch('/panel/admins/{user:username}/toggle', [AdminController::class, 'toggle'])->middleware(['auth.panel', 'super'])->name('panel.admins.toggle');
Route::delete('/panel/admins/{user:username}', [AdminController::class, 'destroy'])->middleware(['auth.panel', 'super'])->name('panel.admins.destroy');
Route::get('/panel/activity/export', ActivityLogExportController::class)->middleware(['auth.panel', 'staff'])->name('panel.activity.export');
Route::post('/panel/bloggers', [BloggerController::class, 'store'])->middleware(['auth.panel', 'staff'])->name('panel.bloggers.store');
Route::patch('/panel/bloggers/{blogger:code}/toggle', [BloggerController::class, 'toggle'])->middleware(['auth.panel', 'staff'])->name('panel.bloggers.toggle');
Route::post('/panel/bloggers/{blogger:code}/avatar', [BloggerController::class, 'avatar'])->middleware(['auth.panel', 'staff'])->name('panel.bloggers.avatar');
Route::delete('/panel/bloggers/{blogger:code}', [BloggerController::class, 'destroy'])->middleware(['auth.panel', 'super'])->name('panel.bloggers.destroy');
Route::post('/panel/r/{registration:ticket_code}/calls', RegistrationCallController::class)->middleware(['auth.panel', 'staff'])->name('panel.registration.calls');
Route::put('/panel/r/{registration:ticket_code}/status', RegistrationStatusController::class)->middleware(['auth.panel', 'staff'])->name('panel.registration.status');
Route::post('/panel/r/{registration:ticket_code}/sms', RegistrationSmsController::class)->middleware(['auth.panel', 'staff'])->name('panel.registration.sms');
Route::post('/panel/sms-templates', [SmsTemplateController::class, 'store'])->middleware(['auth.panel', 'staff'])->name('panel.sms-templates.store');
Route::delete('/panel/sms-templates/{smsTemplate}', [SmsTemplateController::class, 'destroy'])->middleware(['auth.panel', 'staff'])->name('panel.sms-templates.destroy');
Route::get('/s/{blogger:code}', [ReferenceDesignController::class, 'landing'])->name('landing');
Route::get('/ticket/{registration:ticket_code}', [ReferenceDesignController::class, 'ticket'])->middleware('signed')->name('ticket.show');
Route::get('/design/support.js', [ReferenceDesignController::class, 'support'])->name('design.support');
Route::get('/design/assets/{asset}', [ReferenceDesignController::class, 'asset'])->where('asset', '[A-Za-z0-9._-]+')->name('design.assets');
