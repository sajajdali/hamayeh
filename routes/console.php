<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run --only-db')
    ->dailyAt('02:30')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping(180)
    ->onOneServer()
    ->environments(['production']);

Schedule::command('backup:clean')
    ->dailyAt('03:30')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping(180)
    ->onOneServer()
    ->environments(['production']);

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->onOneServer();
