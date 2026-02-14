<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Activate scheduled menu item prices daily at midnight
Schedule::command('price:activate-scheduled')->dailyAt('00:00')
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduled-prices.log'));
