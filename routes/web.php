<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::panel-login')->name('home');
Route::livewire('/s/{blogger:code}', 'pages::landing-blogger')->name('landing');
