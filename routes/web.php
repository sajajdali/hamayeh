<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing-home')->name('home');
Route::livewire('/{blogger:slug}', 'pages::landing-blogger')->name('landing');
