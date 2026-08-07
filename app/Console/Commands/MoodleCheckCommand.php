<?php

namespace App\Console\Commands;

use App\Models\TaskReminder;
use App\Models\User;
use App\Services\MoodleService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MoodleCheckCommand extends Command
{
    protected $signature   = 'moodle:check';
    protected $description = 'Fetch Moodle tasks and send Telegram reminders (anti-spam)';

    public function __construct(
        private MoodleService   $moodle,
        private TelegramService $telegram
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('[' . now()->toDateTimeString() . '] Starting Moodle check...');

        // Only process eligible users (active + has access + Telegram linked)
        User::eligible()->chunk(50, function ($users) {
            foreach ($users as $user) {
                $this->processUser($user);
                // Polite delay to avoid hammering Moodle server
                usleep(200_000); // 0.2s per user × 50 = 10s total (safe for Render 30s timeout)
            }
        });

        $this->info('[' . now()->toDateTimeString() . '] Done.');
        return self::SUCCESS;
    }

    private function processUser(User $user): void
    {
        try {
            $rawTasks = $this->moodle->fetchUpcomingTasks($user->moodle_token);
            $user->update(['last_checked_at' => now(), 'last_moodle_error' => null]);

            foreach ($rawTasks as $rawTask) {
                $taskData = $this->moodle->parseTask($rawTask);
                $this->processTask($user, $taskData);
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::warning("Moodle check failed for user {$user->id}", ['error' => $errorMsg]);

            $user->update(['last_moodle_error' => $errorMsg]);

            // If token is invalid, notify user to re-login
            if ($errorMsg === 'INVALID_TOKEN') {
                $this->telegram->sendMessage(
                    $user->telegram_chat_id,
                    "⚠️ <b>Sesi Moodle Kamu Berakhir!</b>\n\n"
                    . "Halo <b>{$user->name}</b>, sepertinya kamu baru saja mengganti password Ilmu 2.\n\n"
                    . "Silakan login ulang di website kami untuk menghubungkan kembali:\n"
                    . "🔗 <b>https://radarilmu2.site</b>"
                );
            }
        }
    }

    private function processTask(User $user, array $taskData): void
    {
        $now      = now();
        $deadline = $taskData['deadline'];
        $daysLeft = (int) $now->diffInDays($deadline, false);

        // Skip tasks that are already past deadline
        if ($daysLeft < 0) return;

        // Find or create the task reminder record (idempotent)
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

        // ── NEW TASK ALERT ─────────────────────────────────────────────────────
        if (!$reminder->new_task_sent) {
            $this->telegram->sendNewTaskAlert($user, $reminder);
            $reminder->update(['new_task_sent' => true]);
            $this->info("  [NEW TASK] User:{$user->id} Task:{$reminder->moodle_task_id}");
            return; // Don't send more alerts on first detection
        }

        // ── H-7 REMINDER ──────────────────────────────────────────────────────
        if (!$reminder->h_7_sent && $daysLeft <= 7 && $daysLeft > 3) {
            $this->telegram->sendH7Alert($user, $reminder);
            $reminder->update(['h_7_sent' => true]);
            $this->info("  [H-7] User:{$user->id} Task:{$reminder->moodle_task_id}");
        }

        // ── H-1 REMINDER ──────────────────────────────────────────────────────
        if (!$reminder->h_1_sent && $daysLeft <= 1) {
            $this->telegram->sendH1Alert($user, $reminder);
            $reminder->update(['h_1_sent' => true]);
            $this->info("  [H-1] User:{$user->id} Task:{$reminder->moodle_task_id}");
        }
    }
}
