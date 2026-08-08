<?php

namespace App\Http\Controllers;

use App\Models\PaymentVerification;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(private TelegramService $telegram) {}

    /**
     * Main Telegram webhook entry point.
     * All updates from Telegram are delivered here.
     */
    public function handle(Request $request)
    {
        $update = $request->all();
        Log::debug('Telegram webhook received', ['update' => $update]);

        try {
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            } elseif (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (\Exception $e) {
            Log::error('Telegram webhook handler error', ['message' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }

    // ─── Message Handler ──────────────────────────────────────────────────────

    private function handleMessage(array $message): void
    {
        $chatId   = $message['chat']['id'];
        $text     = $message['text'] ?? '';
        $isPhoto  = isset($message['photo']);
        $isAdmin  = ($chatId == $this->telegram->getAdminChatId());

        if ($isPhoto) {
            $this->handlePaymentProof($message);
            return;
        }

        if (empty($text)) return;

        // Route commands
        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $text, $isAdmin);
        } elseif ($text === '/status') {
            $this->handleStatus($chatId);
        } elseif ($text === '/pause') {
            $this->handlePause($chatId);
        } elseif ($text === '/lanjut') {
            $this->handleResume($chatId);
        } elseif ($text === '/hapus_akun') {
            $this->handleDeleteAccount($chatId);
        } elseif ($text === '/langganan') {
            $this->handleSubscription($chatId);
        } elseif ($isAdmin && $text === '/rekap') {
            $this->handleAdminRecap($chatId);
        } else {
            $this->handleUnknown($chatId, $isAdmin);
        }
    }

    // ─── /start (Deep Link Handshake) ────────────────────────────────────────

    private function handleStart(int $chatId, string $text, bool $isAdmin): void
    {
        $parts    = explode(' ', $text);
        $syncCode = $parts[1] ?? null;

        if ($syncCode && str_starts_with($syncCode, 'SYNC-')) {
            // Find the user with this sync code
            $user = User::where('sync_code', $syncCode)->first();

            if (!$user) {
                $this->telegram->sendMessage($chatId,
                    "❌ <b>Kode tidak valid atau sudah kadaluarsa.</b>\n\n"
                    . "Silakan kembali ke website dan login ulang untuk mendapatkan link baru."
                );
                return;
            }

            // Link Telegram Chat ID to user
            $user->update([
                'telegram_chat_id' => (string) $chatId,
                'sync_code'        => null, // invalidate sync code
                'trial_started_at' => $user->trial_started_at ?? now(),
            ]);

            $trialEndDate = $user->getTrialExpiresAt();
            $trialText    = $trialEndDate
                ? "\n\n🎁 <b>Free Trial aktif hingga:</b> " . $trialEndDate->locale('id')->isoFormat('D MMMM YYYY')
                : '';

            $this->telegram->sendMessage($chatId,
                "🎉 <b>Berhasil Terhubung!</b>\n\n"
                . "Halo <b>{$user->name}</b>! 👋 Selamat datang di <b>Radar Ilmu 2</b>.\n\n"
                . "Akun Telegram kamu berhasil dihubungkan dengan sistem kami! "
                . "Mulai sekarang, kamu fokus aja sama urusan kampus, biar aku yang jagain deadline tugasmu. "
                . "Aku akan ngirim notifikasi secara berkala ya! 🚀"
                . $trialText
                . "\n\n📋 Ketik /status untuk cek kondisi akunmu."
            );
            return;
        }

        // Generic /start (no sync code)
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if ($user) {
            $this->telegram->sendMessage($chatId,
                "👋 Halo lagi, <b>{$user->name}</b>!\n\n"
                . "Akunmu sudah terhubung. Ketik /status untuk melihat detail.\n\n"
                . "Menu:\n"
                . "/status - Cek status koneksi\n"
                . "/langganan - Perpanjang masa aktif\n"
                . "/pause - Hentikan notifikasi sementara\n"
                . "/hapus_akun - Hapus akun & data"
            );
        } else {
            $this->telegram->sendMessage($chatId,
                "👋 Halo! Saya adalah <b>Radar Ilmu 2 Bot</b>.\n\n"
                . "Untuk menggunakan bot ini, daftarkan diri kamu melalui website kami terlebih dahulu:\n"
                . "🔗 <b>https://radarilmu2.site</b>\n\n"
                . "Setelah login dengan akun UPN Jatim kamu, klik tombol \"Hubungkan ke Telegram\" di website."
            );
        }
    }

    // ─── /status ─────────────────────────────────────────────────────────────

    private function handleStatus(int $chatId): void
    {
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if (!$user) { $this->sendNotRegistered($chatId); return; }

        $moodleStatus  = $user->hasMoodleToken() ? '✅ Terhubung' : '❌ Belum terhubung';
        $activeStatus  = $user->is_active ? '✅ Aktif' : '⏸ Dijeda';
        $accessStatus  = $user->hasActiveAccess() ? '✅ Aktif' : '❌ Habis / Belum bayar';
        $pendingTasks  = $user->taskReminders()->where('deadline', '>=', now())->count();

        if ($user->subscription_status && $user->subscription_expires_at) {
            $expires = $user->subscription_expires_at->locale('id')->isoFormat('D MMMM YYYY');
            $subInfo = "💳 <b>Langganan aktif hingga:</b> {$expires}";
        } elseif (!$user->trial_used && $user->trial_started_at) {
            $expires = $user->getTrialExpiresAt();
            $subInfo = $expires
                ? "🎁 <b>Free Trial hingga:</b> " . $expires->locale('id')->isoFormat('D MMMM YYYY')
                : "🎁 <b>Free Trial:</b> Aktif";
        } else {
            $subInfo = "❌ <b>Akses:</b> Tidak aktif — ketik /langganan untuk berlangganan";
        }

        $this->telegram->sendMessage($chatId,
            "📊 <b>STATUS AKUN - RADAR ILMU 2</b>\n\n"
            . "👤 <b>Nama:</b> {$user->name}\n"
            . "📧 <b>Email:</b> {$user->email}\n"
            . "🔗 <b>Moodle:</b> {$moodleStatus}\n"
            . "🔔 <b>Notifikasi:</b> {$activeStatus}\n"
            . "🎯 <b>Akses:</b> {$accessStatus}\n"
            . "{$subInfo}\n"
            . "📚 <b>Tugas terdeteksi:</b> {$pendingTasks} tugas aktif\n\n"
            . "Ketik /pause untuk jedakan notifikasi atau /langganan untuk perpanjang akses."
        );
    }

    // ─── /pause ───────────────────────────────────────────────────────────────

    private function handlePause(int $chatId): void
    {
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if (!$user) { $this->sendNotRegistered($chatId); return; }

        $user->update(['is_active' => false]);
        $this->telegram->sendMessage($chatId,
            "⏸ <b>Notifikasi dimatikan sementara.</b>\n\n"
            . "Kamu tidak akan menerima pengingat tugas selama mode pause aktif.\n"
            . "Ketik /lanjut untuk menghidupkan kembali."
        );
    }

    // ─── /lanjut ─────────────────────────────────────────────────────────────

    private function handleResume(int $chatId): void
    {
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if (!$user) { $this->sendNotRegistered($chatId); return; }

        $user->update(['is_active' => true]);
        $this->telegram->sendMessage($chatId,
            "▶️ <b>Notifikasi aktif kembali!</b>\n\n"
            . "Aku akan mulai memantau tugas Moodle kamu lagi. 🚀"
        );
    }

    // ─── /hapus_akun ──────────────────────────────────────────────────────────

    private function handleDeleteAccount(int $chatId): void
    {
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if (!$user) { $this->sendNotRegistered($chatId); return; }

        // Cascade deletes task_reminders and payment_verifications
        $user->delete();

        $this->telegram->sendMessage($chatId,
            "👋 <b>Akun telah dihapus.</b>\n\n"
            . "Data kamu (Token Moodle & Riwayat Tugas) telah dihapus sepenuhnya dari sistem kami.\n\n"
            . "Terima kasih sudah menggunakan layanan ini! Semoga sukses ya kuliah dan kegiatannya! 🎓"
        );
    }

    // ─── /langganan ───────────────────────────────────────────────────────────

    private function handleSubscription(int $chatId): void
    {
        $user = User::where('telegram_chat_id', (string) $chatId)->first();
        if (!$user) { $this->sendNotRegistered($chatId); return; }

        $qrisPath = public_path('images/qris.jpeg');

        $caption = "💳 <b>BERLANGGANAN RADAR ILMU 2</b>\n\n"
                 . "Harga: <b>Rp 5.000 / bulan</b>\n\n"
                 . "Scan QRIS di bawah ini menggunakan:\n"
                 . "📱 GoPay, OVO, DANA, ShopeePay, M-Banking\n\n"
                 . "✅ <b>Setelah bayar:</b>\n"
                 . "Kirimkan <b>screenshot bukti pembayaran</b> ke chat ini.\n"
                 . "Admin akan memverifikasi dalam beberapa menit.\n\n"
                 . "❓ Ada pertanyaan? Hubungi admin di @kakaadsp";

        if (file_exists($qrisPath)) {
            $this->telegram->sendPhoto($chatId, $qrisPath, $caption);
        } else {
            $this->telegram->sendMessage($chatId, $caption . "\n\n⚠️ (QRIS image sedang dimuat, hubungi @kakaadsp)");
        }
    }

    // ─── Payment Proof Handler ────────────────────────────────────────────────

    private function handlePaymentProof(array $message): void
    {
        $chatId = $message['chat']['id'];
        $user   = User::where('telegram_chat_id', (string) $chatId)->first();

        if (!$user) { $this->sendNotRegistered($chatId); return; }

        // Get highest resolution photo file_id
        $photos = $message['photo'];
        $fileId = end($photos)['file_id'];

        // Create pending payment record
        $payment = PaymentVerification::create([
            'user_id'          => $user->id,
            'telegram_file_id' => $fileId,
            'status'           => 'pending',
        ]);

        // Notify admin
        $this->telegram->notifyAdminPayment($user, $fileId, $payment->id);

        // Acknowledge to user
        $this->telegram->sendMessage($chatId,
            "📬 <b>Bukti pembayaran diterima!</b>\n\n"
            . "Terima kasih <b>{$user->name}</b>!\n"
            . "Admin sedang memverifikasi pembayaran kamu.\n"
            . "Masa aktif akan diaktifkan segera setelah verifikasi berhasil.\n\n"
            . "Biasanya proses ini memakan waktu 5-15 menit. ⏱"
        );
    }

    // ─── Callback Query (Admin Approve/Reject) ────────────────────────────────

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId          = $callbackQuery['message']['chat']['id'];
        $messageId       = $callbackQuery['message']['message_id'];
        $callbackQueryId = $callbackQuery['id'];
        $data            = $callbackQuery['data'] ?? '';

        // Security: Only admin can process these callbacks
        if ($chatId != $this->telegram->getAdminChatId()) {
            $this->telegram->answerCallbackQuery($callbackQueryId, '⛔ Anda tidak memiliki akses!', true);
            return;
        }

        [$action, $paymentId] = array_pad(explode(':', $data), 2, null);

        if (!$paymentId) return;

        $payment = PaymentVerification::with('user')->find($paymentId);
        if (!$payment || !$payment->isPending()) {
            $this->telegram->answerCallbackQuery($callbackQueryId, '⚠️ Pembayaran ini sudah diproses.', true);
            return;
        }

        if ($action === 'approve_payment') {
            $payment->update([
                'status'      => 'approved',
                'approved_at' => now(),
            ]);

            // Activate subscription for 30 days
            $payment->user->update([
                'subscription_status'     => true,
                'subscription_expires_at' => now()->addDays(30),
                'trial_used'              => true,
            ]);

            // Notify the student
            $expiry = now()->addDays(30)->locale('id')->isoFormat('D MMMM YYYY');
            $this->telegram->sendMessage($payment->user->telegram_chat_id,
                "✅ <b>Pembayaran Berhasil Diverifikasi!</b>\n\n"
                . "Halo <b>{$payment->user->name}</b>!\n"
                . "Langganan <b>Radar Ilmu 2</b> kamu telah aktif! 🎉\n\n"
                . "📅 <b>Aktif hingga:</b> {$expiry}\n\n"
                . "Aku akan terus memantau tugas Moodle kamu dan mengirim notifikasi tepat waktu. Semangat kuliah! 🚀"
            );

            $this->telegram->answerCallbackQuery($callbackQueryId, '✅ Berhasil diapprove!');
            $this->telegram->editMessageText($chatId, $messageId,
                $callbackQuery['message']['caption'] ?? ''
                . "\n\n✅ <b>APPROVED</b> - Diverifikasi oleh Admin"
            );

        } elseif ($action === 'reject_payment') {
            $payment->update(['status' => 'rejected']);

            $this->telegram->sendMessage($payment->user->telegram_chat_id,
                "❌ <b>Pembayaran Tidak Terverifikasi</b>\n\n"
                . "Maaf <b>{$payment->user->name}</b>, bukti pembayaran kamu tidak dapat diverifikasi.\n\n"
                . "Kemungkinan alasan:\n"
                . "• Screenshot tidak jelas\n"
                . "• Nominal tidak sesuai (Rp 5.000)\n"
                . "• Pembayaran belum masuk ke rekening Admin\n\n"
                . "Coba kirim ulang bukti pembayaran atau hubungi @kakaadsp untuk bantuan."
            );

            $this->telegram->answerCallbackQuery($callbackQueryId, '❌ Pembayaran ditolak.');
            $this->telegram->editMessageText($chatId, $messageId,
                ($callbackQuery['message']['caption'] ?? '')
                . "\n\n❌ <b>REJECTED</b>"
            );
        }
    }

    // ─── Admin Recap ──────────────────────────────────────────────────────────

    private function handleAdminRecap(int $chatId): void
    {
        $totalUsers    = User::count();
        $activeUsers   = User::where('subscription_status', true)
                             ->where('subscription_expires_at', '>=', now())->count();
        $trialUsers    = User::where('trial_used', false)->count();
        $pendingPayments = PaymentVerification::where('status', 'pending')->count();
        $revenue       = PaymentVerification::where('status', 'approved')->sum('amount');

        $this->telegram->sendMessage($chatId,
            "📊 <b>REKAP ADMIN - RADAR ILMU 2</b>\n\n"
            . "👥 <b>Total User:</b> {$totalUsers}\n"
            . "💳 <b>Berlangganan Aktif:</b> {$activeUsers}\n"
            . "🎁 <b>User Trial:</b> {$trialUsers}\n"
            . "⏳ <b>Pembayaran Pending:</b> {$pendingPayments}\n"
            . "💰 <b>Total Pendapatan:</b> Rp " . number_format($revenue, 0, ',', '.') . "\n"
        );
    }

    // ─── Unknown Command ──────────────────────────────────────────────────────

    private function handleUnknown(int $chatId, bool $isAdmin): void
    {
        $menu = "/status - Cek status akun\n"
              . "/langganan - Berlangganan / perpanjang\n"
              . "/pause - Jedakan notifikasi\n"
              . "/lanjut - Aktifkan notifikasi\n"
              . "/hapus_akun - Hapus akun & data";

        if ($isAdmin) {
            $menu .= "\n/rekap - Rekap statistik admin";
        }

        $this->telegram->sendMessage($chatId,
            "🤖 <b>Perintah yang tersedia:</b>\n\n" . $menu
        );
    }

    private function sendNotRegistered(int $chatId): void
    {
        $this->telegram->sendMessage($chatId,
            "❓ Akun kamu belum terdaftar.\n\n"
            . "Daftar melalui website kami:\n"
            . "🔗 <b>https://radarilmu2.site</b>"
        );
    }
}
