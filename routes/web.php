<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ─────────────────────────────────────────────────────────────
Route::get('/',      [AuthController::class, 'index'])->name('landing');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login',[AuthController::class, 'login'])->name('login');
Route::get('/logout',[AuthController::class, 'logout'])->name('logout');

// ─── Authenticated Routes ──────────────────────────────────────────────────────
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

// ─── Telegram Webhook (no CSRF) ───────────────────────────────────────────────
Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');

// ─── Cron Reminder Endpoint (no CSRF, secured by token query param) ───────────
Route::get('/api/send-reminders', [ReminderController::class, 'sendReminders'])
    ->name('cron.reminders');

// ─── Keep-Alive Ping (prevents Render free plan from sleeping) ─────────────────
Route::get('/api/ping', function () {
    return response()->json([
        'status' => 'ok',
        'time'   => now()->toIso8601String(),
        'app'    => config('app.name'),
    ]);
})->name('ping');
