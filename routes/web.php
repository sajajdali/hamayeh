<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::panel-login')->name('home');
Route::livewire('/s/{blogger:code}', 'pages::landing-blogger')->name('landing');
Route::get('/ticket/{registration:ticket_code}', TicketController::class)->middleware('signed')->name('ticket.show');
