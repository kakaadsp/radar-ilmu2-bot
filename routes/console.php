<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Task Scheduling ──────────────────────────────────────────────────────────
// Run Moodle check every 30 minutes
Schedule::command('moodle:check')->everyThirtyMinutes();

// Send subscription expiry warnings (3 days before)
Schedule::command('subscriptions:warn')->daily();
