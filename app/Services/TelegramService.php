<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $baseUrl;
    private int    $adminChatId;

    public function __construct()
    {
        $this->token       = config('services.telegram.bot_token');
        $this->adminChatId = (int) config('services.telegram.admin_chat_id');
        $this->baseUrl     = "https://api.telegram.org/bot{$this->token}";
    }

    // ─── Core Send Methods ────────────────────────────────────────────────────

    /**
     * Send a plain text message.
     */
    public function sendMessage(string|int $chatId, string $text, array $extra = []): array|null
    {
        return $this->call('sendMessage', array_merge([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    /**
     * Send a photo with optional caption.
     */
    public function sendPhoto(string|int $chatId, string $photoPathOrUrl, string $caption = '', array $extra = []): array|null
    {
        $payload = array_merge([
            'chat_id'    => $chatId,
            'caption'    => $caption,
            'parse_mode' => 'HTML',
        ], $extra);

        // Send local file vs URL
        if (file_exists($photoPathOrUrl)) {
            $response = Http::attach('photo', file_get_contents($photoPathOrUrl), basename($photoPathOrUrl))
                ->post("{$this->baseUrl}/sendPhoto", $payload);
            return $response->json();
        }

        return $this->call('sendPhoto', array_merge($payload, ['photo' => $photoPathOrUrl]));
    }

    /**
     * Forward a photo file by file_id.
     */
    public function forwardPhotoByFileId(string|int $chatId, string $fileId, string $caption = '', array $extra = []): array|null
    {
        return $this->call('sendPhoto', array_merge([
            'chat_id'    => $chatId,
            'photo'      => $fileId,
            'caption'    => $caption,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    /**
     * Edit message text.
     */
    public function editMessageText(string|int $chatId, int $messageId, string $text): array|null
    {
        return $this->call('editMessageText', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * Answer a callback query (hides the loading spinner on inline button).
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array|null
    {
        return $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text'              => $text,
            'show_alert'        => $showAlert,
        ]);
    }

    // ─── Keyboard Builders ────────────────────────────────────────────────────

    /**
     * Build a JSON-encoded inline keyboard.
     */
    public function inlineKeyboard(array $buttons): string
    {
        return json_encode(['inline_keyboard' => $buttons]);
    }

    // ─── Notification Templates ───────────────────────────────────────────────

    /**
     * Send "new task detected" notification.
     */
    public function sendNewTaskAlert(\App\Models\User $user, \App\Models\TaskReminder $task): void
    {
        $deadline = $task->deadline->locale('id')->isoFormat('dddd, D MMMM YYYY (HH:mm)');
        $daysLeft = max(0, $task->getDaysRemaining());

        $text = "🔔 <b>TUGAS BARU DARI DOSEN!</b>\n\n"
              . "🚨 <b>Gawat, Ada Tugas Baru di Ilmu 2!</b>\n\n"
              . "Halo <b>{$user->name}</b>! Dosen baru saja menambahkan penugasan:\n\n"
              . "📚 <b>Mata Kuliah:</b> {$task->course_fullname}\n"
              . "📝 <b>Tugas:</b> {$task->task_name}\n"
              . "🗓 <b>Deadline:</b> {$deadline} WIB\n"
              . "⏳ <b>Sisa Waktu:</b> {$daysLeft} hari lagi\n\n"
              . "Sistem sudah mencatat tugas ini. Aku akan mulai ngingetin kamu lagi saat H-7 nanti. Semangat! 💻🔥";

        $keyboard = $this->inlineKeyboard([[
            ['text' => '📖 Buka Tugas di Ilmu 2', 'url' => $task->moodle_url ?? 'https://ilmu2.upnjatim.ac.id'],
        ]]);

        $this->sendMessage($user->telegram_chat_id, $text, ['reply_markup' => $keyboard]);
    }

    /**
     * Send H-7 reminder.
     */
    public function sendH7Alert(\App\Models\User $user, \App\Models\TaskReminder $task): void
    {
        $deadline = $task->deadline->locale('id')->isoFormat('dddd, D MMMM YYYY (HH:mm)');

        $text = "🚨 <b>TUGAS BARU TERDETEKSI! (H-7)</b> 🚨\n\n"
              . "Halo <b>{$user->name}</b>! Jangan sampai lupa, ada tugas Moodle yang menanti:\n\n"
              . "📚 <b>Mata Kuliah:</b> {$task->course_fullname}\n"
              . "📝 <b>Tugas:</b> {$task->task_name}\n"
              . "🗓 <b>Deadline:</b> {$deadline} WIB\n"
              . "⏳ <b>Sisa Waktu:</b> 7 Hari lagi\n\n"
              . "Nyicil dari sekarang yuk, sebelum numpuk! 🚀";

        $keyboard = $this->inlineKeyboard([[
            ['text' => '📖 Buka Tugas di Ilmu 2', 'url' => $task->moodle_url ?? 'https://ilmu2.upnjatim.ac.id'],
        ]]);

        $this->sendMessage($user->telegram_chat_id, $text, ['reply_markup' => $keyboard]);
    }

    /**
     * Send H-3 reminder.
     */
    public function sendH3Alert(\App\Models\User $user, \App\Models\TaskReminder $task): void
    {
        $deadline = $task->deadline->locale('id')->isoFormat('dddd, D MMMM YYYY (HH:mm)');

        $text = "⏰ <b>DEADLINE 3 HARI LAGI! (H-3)</b> ⏰\n\n"
              . "Halo <b>{$user->name}</b>! Masih ada waktu, tapi jangan ditunda ya:\n\n"
              . "📚 <b>Mata Kuliah:</b> {$task->course_fullname}\n"
              . "📝 <b>Tugas:</b> {$task->task_name}\n"
              . "🗓 <b>Deadline:</b> {$deadline} WIB\n"
              . "⏳ <b>Sisa Waktu:</b> 3 Hari lagi\n\n"
              . "Gas kerjain sekarang biar nggak panik H-1! 💪🔥";

        $keyboard = $this->inlineKeyboard([[
            ['text' => '📖 Buka Tugas di Ilmu 2', 'url' => $task->moodle_url ?? 'https://ilmu2.upnjatim.ac.id'],
        ]]);

        $this->sendMessage($user->telegram_chat_id, $text, ['reply_markup' => $keyboard]);
    }

    /**
     * Send H-1 urgent reminder.
     */
    public function sendH1Alert(\App\Models\User $user, \App\Models\TaskReminder $task): void
    {
        $deadline = $task->deadline->locale('id')->isoFormat('dddd, D MMMM YYYY (HH:mm)');

        $text = "⚠️ <b>DEADLINE BESOK BOS! (H-1)</b> ⚠️\n\n"
              . "<b>{$user->name}</b>, tugas ini belum kamu kumpulkan di Moodle!\n\n"
              . "📚 <b>Mata Kuliah:</b> {$task->course_fullname}\n"
              . "📝 <b>Tugas:</b> {$task->task_name}\n"
              . "🗓 <b>Deadline:</b> BESOK ({$deadline} WIB)\n"
              . "⏳ <b>Sisa Waktu:</b> Kurang dari 24 Jam!\n\n"
              . "Tinggalkan kerjaan lain bentar, ayo kerjain sekarang sebelum portal ditutup! 🔥";

        $keyboard = $this->inlineKeyboard([[
            ['text' => '🚀 Langsung Kumpulkan Sekarang!', 'url' => $task->moodle_url ?? 'https://ilmu2.upnjatim.ac.id'],
        ]]);

        $this->sendMessage($user->telegram_chat_id, $text, ['reply_markup' => $keyboard]);
    }

    /**
     * Notify admin about a new payment receipt uploaded by user.
     */
    public function notifyAdminPayment(\App\Models\User $user, string $fileId, int $paymentId): void
    {
        Log::info('Notifying admin about payment', [
            'admin_chat_id' => $this->adminChatId,
            'user_id'       => $user->id,
            'payment_id'    => $paymentId,
            'file_id'       => $fileId,
        ]);

        $keyboard = $this->inlineKeyboard([[
            ['text' => '✅ Approve', 'callback_data' => "approve_payment:{$paymentId}"],
            ['text' => '❌ Reject',  'callback_data' => "reject_payment:{$paymentId}"],
        ]]);

        $caption = "💰 <b>PEMBAYARAN BARU!</b>\n\n"
                 . "👤 <b>Nama:</b> {$user->name}\n"
                 . "📧 <b>Email:</b> {$user->email}\n"
                 . "💵 <b>Jumlah:</b> Rp 5.000\n"
                 . "🆔 <b>User ID:</b> {$user->id}\n\n"
                 . "Cek GoPay Merchant-mu, lalu klik tombol di bawah untuk memverifikasi!";

        $result = $this->forwardPhotoByFileId($this->adminChatId, $fileId, $caption, ['reply_markup' => $keyboard]);

        if (!$result || !($result['ok'] ?? false)) {
            Log::error('Failed to notify admin about payment', [
                'admin_chat_id' => $this->adminChatId,
                'payment_id'    => $paymentId,
                'telegram_error'=> $result['description'] ?? 'unknown error',
                'response'      => $result,
            ]);
        } else {
            Log::info('Admin notified successfully about payment', ['payment_id' => $paymentId]);
        }
    }

    // ─── Raw API Call ─────────────────────────────────────────────────────────

    private function call(string $method, array $params): array|null
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/{$method}", $params);
            $json = $response->json();

            // Log any failed API responses (ok: false)
            if (!($json['ok'] ?? true)) {
                Log::warning("Telegram API returned ok=false [{$method}]", [
                    'description' => $json['description'] ?? 'no description',
                    'error_code'  => $json['error_code'] ?? null,
                    'params'      => array_diff_key($params, ['photo' => '', 'reply_markup' => '']),
                ]);
            }

            return $json;
        } catch (\Exception $e) {
            Log::error("Telegram API exception [{$method}]", ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function getAdminChatId(): int
    {
        return $this->adminChatId;
    }
}
