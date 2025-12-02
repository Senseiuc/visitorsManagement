<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule queue worker to process email notifications
Schedule::command('queue:work --stop-when-empty --max-time=3600')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
