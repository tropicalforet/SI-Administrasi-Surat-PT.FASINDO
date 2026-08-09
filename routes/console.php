<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat dan eskalasi tenggat disposisi dijalankan sekali setiap pagi.
Schedule::command('disposisi:periksa-tenggat')
    ->dailyAt('07:00')
    ->withoutOverlapping();
