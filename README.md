# Radar Ilmu 2 Bot 🎓📡

> Asisten pintar yang otomatis memantau tugas Moodle UPN Jatim dan mengirim pengingat ke Telegram.

---

## 🚀 Quick Start (Lokal)

### Prasyarat
- PHP 8.2+ dengan extension `pdo_pgsql` aktif
- Composer
- Node.js & npm

### Instalasi

```bash
# Clone repo
git clone https://github.com/kakaadsp/radar-ilmu2-bot.git
cd radar-ilmu2-bot

# Install dependencies
composer install
npm install

# Copy env
cp .env.example .env

# Generate app key
php artisan key:generate

# Jalankan migrasi (Supabase sudah dikonfigurasi)
php artisan migrate

# Start server
php artisan serve
```

Buka: `http://localhost:8000`

---

## ⚙️ Environment Variables

| Variable | Keterangan |
|---|---|
| `DB_HOST` | Supabase PostgreSQL Host |
| `DB_PASSWORD` | Supabase PostgreSQL Password |
| `TELEGRAM_BOT_TOKEN` | Token dari @BotFather |
| `TELEGRAM_ADMIN_CHAT_ID` | Chat ID kamu (cek via @userinfobot) |
| `MOODLE_BASE_URL` | `https://ilmu2.upnjatim.ac.id` |
| `TRIAL_DAYS` | Durasi free trial (default: 7) |
| `SUBSCRIPTION_PRICE` | Harga langganan dalam rupiah |

---

## 📁 Struktur Project

```
radar-ilmu2-bot/
├── app/
│   ├── Console/Commands/
│   │   └── MoodleCheckCommand.php     # Cron job utama (php artisan moodle:check)
│   ├── Http/Controllers/
│   │   ├── AuthController.php         # Login via Moodle + session
│   │   └── TelegramWebhookController.php # Semua logika bot Telegram
│   ├── Models/
│   │   ├── User.php                   # Model user dengan subscription helpers
│   │   ├── TaskReminder.php           # Model task reminder (anti-spam logic)
│   │   └── PaymentVerification.php    # Model payment (QRIS semi-auto)
│   └── Services/
│       ├── MoodleService.php          # HTTP client ke API Ilmu 2
│       └── TelegramService.php        # Telegram Bot API client + templates
├── database/migrations/
│   ├── ..._create_users_table.php
│   ├── ..._create_task_reminders_table.php
│   └── ..._create_payment_verifications_table.php
├── resources/views/
│   ├── landing.blade.php              # Landing page premium
│   └── dashboard.blade.php            # SaaS dashboard
├── routes/
│   ├── web.php                        # Routes web + webhook
│   └── console.php                    # Scheduler (every 30 min)
└── public/
    └── images/
        ├── qris.jpg                   # QRIS GoPay kamu
        └── mascot.png                 # Logo maskot
```

---

## 🤖 Bot Commands

| Command | Fungsi |
|---|---|
| `/start SYNC_CODE` | Handshake & hubungkan akun |
| `/status` | Cek status akun & koneksi |
| `/langganan` | Tampilkan QRIS & instruksi bayar |
| `/pause` | Hentikan notifikasi sementara |
| `/lanjut` | Aktifkan notifikasi kembali |
| `/hapus_akun` | Hapus akun & data permanen |
| `/rekap` | (Admin only) Statistik pendapatan |

---

## 🗄️ Database Schema

### `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BigInt | Primary Key |
| `name` | String | Nama mahasiswa |
| `email` | String | Email UPN (unique) |
| `moodle_token` | String | Token Moodle (aman) |
| `telegram_chat_id` | String | Chat ID Telegram |
| `sync_code` | String | Kode sinkronisasi sementara |
| `is_active` | Boolean | Status notifikasi |
| `subscription_status` | Boolean | Status langganan |
| `subscription_expires_at` | Timestamp | Tanggal kedaluwarsa |
| `trial_used` | Boolean | Sudah pakai trial? |
| `trial_started_at` | Timestamp | Awal trial |

### `task_reminders`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | FK | Cascade delete |
| `moodle_task_id` | String | ID tugas dari Moodle |
| `task_name` | String | Nama tugas |
| `course_fullname` | String | Nama mata kuliah |
| `deadline` | Timestamp | Tenggat waktu |
| `new_task_sent` | Boolean | Alert tugas baru |
| `h_7_sent` | Boolean | Reminder H-7 |
| `h_1_sent` | Boolean | Reminder H-1 |
| `moodle_url` | String | Direct link ke tugas |

---

## 🚂 Deploy ke Railway

1. Push repo ke GitHub
2. Buka [railway.app](https://railway.app) → New Project from GitHub
3. Set environment variables:
   ```
   APP_ENV=production
   APP_KEY=<generate dengan php artisan key:generate>
   DB_HOST=db.sumjwjzcxfrvwfeqyvnw.supabase.co
   DB_PASSWORD=kakasupabase10
   TELEGRAM_BOT_TOKEN=8858039936:AAHk--0j2ihVNiglXhUksIO_bXj1KyFkr80
   TELEGRAM_ADMIN_CHAT_ID=1331412223
   ...
   ```
4. Setelah deploy, daftarkan webhook Telegram:
   ```
   https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://your-domain.railway.app/webhook/telegram
   ```
5. Aktifkan Cron Job di Railway:
   - Command: `php artisan schedule:run`
   - Schedule: `*/30 * * * *` (setiap 30 menit)

---

## 📊 Anti-Spam Logic

```
Setiap 30 menit:
  Foreach user (active + has access) [chunk(50)]:
    Fetch tasks from Moodle API
    Foreach task:
      if task NOT in DB → simpan + kirim [TUGAS BARU]
      elif daysLeft ≤ 7 AND h_7_sent = false → kirim [H-7] + update flag
      elif daysLeft ≤ 1 AND h_1_sent = false → kirim [H-1] + update flag
```

---

## 🔐 Security Notes

- ✅ Password tidak pernah disimpan di database
- ✅ Webhook Telegram hanya bisa diproses admin untuk approve/reject
- ✅ Sync code di-invalidate setelah dipakai
- ✅ Cascade delete (hapus user = hapus semua data)
- ✅ SSL required untuk Supabase connection
- ✅ CSRF exempt hanya untuk endpoint webhook

---

## 💰 Monetisasi

**Model**: SaaS subscription Rp 5.000/bulan  
**Free Trial**: 7 hari tanpa bayar  
**Payment**: Semi-otomatis via QRIS GoPay + verifikasi Admin di Telegram  
**Target**: 2.400 mahasiswa Fasilkom UPN Jatim  
**Revenue Potential**: Rp 12.000.000/bulan (jika 2.400 user x Rp 5.000)

---

*Built with ❤️ by [@kakaadsp](https://github.com/kakaadsp) — UPN "Veteran" Jawa Timur*
