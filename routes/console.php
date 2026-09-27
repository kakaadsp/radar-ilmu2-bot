<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Task Scheduling ──────────────────────────────────────────────────────────
// Fallback scheduler: jika moodle:check dijalankan via artisan schedule:run
// (Produksi menggunakan cron-job.org → GET /api/send-reminders setiap 15 menit)
Schedule::command('moodle:check')->everyFifteenMinutes();
