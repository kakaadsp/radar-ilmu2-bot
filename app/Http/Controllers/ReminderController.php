<?php

namespace App\Http\Controllers;

use App\Models\TaskReminder;
use App\Models\User;
use App\Services\MoodleService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReminderController extends Controller
{
    public function __construct(
        private MoodleService   $moodle,
        private TelegramService $telegram
    ) {}

    /**
     * HTTP endpoint triggered by Cron-job.org every minute.
     * Processes the next 50 eligible users (cursor-based on last_checked_at).
     *
     * URL: GET /api/send-reminders?token=CRON_SECRET
     */
    public function sendReminders(Request $request): JsonResponse
    {
        // ── Security: Validate secret token ──────────────────────────────────
        $secret = config('services.cron.secret');
        if (empty($secret) || $request->query('token') !== $secret) {
            Log::warning('ReminderController: unauthorized access attempt', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $startTime = microtime(true);
        $processed = 0;
        $sent      = 0;
        $errors    = 0;

        // ── Cursor-based batch: pick 50 users who were checked the longest ago ─
        // Users with NULL last_checked_at are always prioritized first.
        $users = User::eligible()
            ->orderByRaw('last_checked_at ASC NULLS FIRST')
            ->limit(50)
            ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message'   => 'No eligible users found.',
                'processed' => 0,
                'sent'      => 0,
                'errors'    => 0,
                'duration_ms' => 0,
            ]);
        }

        foreach ($users as $user) {
            $result = $this->processUser($user);
            $processed++;
            $sent   += $result['sent'];
            $errors += $result['error'] ? 1 : 0;

            // Polite delay between users: 200ms
            // 50 users × 0.2s = 10s total (well within Render's 30s timeout)
            // Telegram rate: ~1 msg/s (safe, max is 30/s)
            usleep(200_000);
        }

        $durationMs = round((microtime(true) - $startTime) * 1000);

        Log::info('ReminderController: batch complete', [
            'processed'   => $processed,
            'sent'        => $sent,
            'errors'      => $errors,
            'duration_ms' => $durationMs,
        ]);

        return response()->json([
            'message'     => 'Batch complete.',
            'processed'   => $processed,
            'sent'        => $sent,
            'errors'      => $errors,
            'duration_ms' => $durationMs,
        ]);
    }

    // ─── Per-User Logic ───────────────────────────────────────────────────────

    private function processUser(User $user): array
    {
        $sent = 0;

        try {
            $rawTasks = $this->moodle->fetchUpcomingTasks($user->moodle_token);

            // Stamp last_checked_at so this user goes to the back of the queue next round
            $user->update([
                'last_checked_at'  => now(),
                'last_moodle_error' => null,
            ]);

            foreach ($rawTasks as $rawTask) {
                $taskData = $this->moodle->parseTask($rawTask);
                $sent    += $this->processTask($user, $taskData) ? 1 : 0;
            }

            return ['sent' => $sent, 'error' => false];

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::warning("ReminderController: Moodle check failed for user {$user->id}", [
                'error' => $errorMsg,
            ]);

            $user->update(['last_moodle_error' => $errorMsg]);

            // Token invalid → notify user to re-login
            if ($errorMsg === 'INVALID_TOKEN') {
                $this->telegram->sendMessage(
                    $user->telegram_chat_id,
                    "⚠️ <b>Sesi Moodle Kamu Berakhir!</b>\n\n"
                    . "Halo <b>{$user->name}</b>, sepertinya kamu baru saja mengganti password Ilmu 2.\n\n"
                    . "Silakan login ulang di website kami untuk menghubungkan kembali:\n"
                    . "🔗 <b>https://radarilmu2.site</b>"
                );
                $sent++;
            }

            return ['sent' => $sent, 'error' => true];
        }
    }

    private function processTask(User $user, array $taskData): bool
    {
        $now      = now();
        $deadline = $taskData['deadline'];
        $daysLeft = (int) $now->diffInDays($deadline, false);

        // Skip overdue tasks
        if ($daysLeft < 0) return false;

        $reminder = TaskReminder::firstOrCreate(
            [
                'user_id'        => $user->id,
                'moodle_task_id' => $taskData['moodle_task_id'],
            ],
            [
                'task_name'       => $taskData['task_name'],
                'course_name'     => $taskData['course_name'],
                'course_fullname' => $taskData['course_fullname'],
                'deadline'        => $deadline,
                'moodle_url'      => $taskData['moodle_url'],
            ]
        );

        // ── NEW TASK ALERT ────────────────────────────────────────────────────
        if (!$reminder->new_task_sent) {
            $this->telegram->sendNewTaskAlert($user, $reminder);
            $reminder->update(['new_task_sent' => true]);
            return true;
        }

        // ── H-7 REMINDER ─────────────────────────────────────────────────────
        if (!$reminder->h_7_sent && $daysLeft <= 7 && $daysLeft > 3) {
            $this->telegram->sendH7Alert($user, $reminder);
            $reminder->update(['h_7_sent' => true]);
            return true;
        }

        // ── H-3 REMINDER ─────────────────────────────────────────────────────
        if (!$reminder->h_3_sent && $daysLeft <= 3 && $daysLeft > 1) {
            $this->telegram->sendH3Alert($user, $reminder);
            $reminder->update(['h_3_sent' => true]);
            return true;
        }

        // ── H-1 REMINDER ─────────────────────────────────────────────────────
        if (!$reminder->h_1_sent && $daysLeft <= 1) {
            $this->telegram->sendH1Alert($user, $reminder);
            $reminder->update(['h_1_sent' => true]);
            return true;
        }

        return false;
    }
}
