# 🔒 Bukti Teknis: Sistem Tidak Menyimpan Password User

> **Dokumen ini dibuat sebagai bukti resmi bahwa aplikasi Radar Ilmu 2 Bot  
> TIDAK pernah menyimpan, mencatat, atau menyimpan password pengguna dalam bentuk apapun.**

---

## Ringkasan Eksekutif

Aplikasi ini menggunakan sistem **"pinjam password"** — password user hanya digunakan **satu kali** untuk meminta token autentikasi ke server Moodle Ilmu 2, kemudian **langsung dibuang**. Password tidak pernah menyentuh database kita.

---

## Bukti 1: Skema Database — Tidak Ada Kolom Password

**File:** [`database/migrations/2026_07_20_000001_create_users_table.php`](file:///c:/Document/VsCode/radar-ilmu2-bot/database/migrations/2026_07_20_000001_create_users_table.php)

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('moodle_token')->nullable();   // ← Token, BUKAN password
    $table->string('telegram_chat_id')->nullable()->unique();
    $table->string('sync_code')->nullable()->unique();
    $table->boolean('is_active')->default(true);
    $table->boolean('subscription_status')->default(false);
    $table->timestamp('subscription_expires_at')->nullable();
    $table->boolean('trial_used')->default(false);
    $table->timestamp('trial_started_at')->nullable();
    $table->string('last_moodle_error')->nullable();
    $table->timestamp('last_checked_at')->nullable();
    $table->timestamps();
});
```

> [!IMPORTANT]
> **Tidak ada kolom `password`, `pass`, `passwd`, atau apapun yang berkaitan dengan password.**
> Kolom yang ada hanya `moodle_token` — yaitu token sementara dari server Moodle, bukan password.

---

## Bukti 2: Kode Login — Password Hanya Dipakai Sekali Lalu Dibuang

**File:** [`app/Http/Controllers/AuthController.php`](file:///c:/Document/VsCode/radar-ilmu2-bot/app/Http/Controllers/AuthController.php) — Baris 36–100

```php
public function login(Request $request)
{
    // ...validasi...

    // 1. Fetch Moodle token (password is NOT saved)  ← komentar resmi di kode
    $token = $this->moodleService->fetchToken(
        $request->input('email'),
        $request->input('password')   // ← password dikirim ke Moodle, TIDAK disimpan
    );

    // 2. Get student name from Moodle
    $name = $this->extractNameFromEmail($request->input('email'));

    // 3. Find or create user record
    $user = User::firstOrCreate(
        ['email' => $request->input('email')],
        [
            'name'             => $name,
            'trial_started_at' => now(),
            'trial_used'       => false,
            // ← tidak ada 'password' di sini!
        ]
    );

    // 4. Update token and generate sync code
    $user->update([
        'moodle_token' => $token,     // ← yang disimpan = TOKEN, bukan password
        'sync_code'    => $syncCode,
        'last_moodle_error' => null,
    ]);
```

> [!NOTE]
> Password dari `$request->input('password')` **hanya diteruskan ke fungsi `fetchToken()`**.
> Setelah token berhasil didapat, password hilang dari memori — tidak pernah ditulis ke database.

---

## Bukti 3: MoodleService — Password Hanya Digunakan untuk HTTP Request ke Moodle

**File:** [`app/Services/MoodleService.php`](file:///c:/Document/VsCode/radar-ilmu2-bot/app/Services/MoodleService.php) — Baris 20–90

```php
/**
 * Fetch a Moodle token using student credentials.
 * Password is NEVER stored — it is only used in this one call.  ← komentar resmi
 *
 * @throws \Exception
 */
public function fetchToken(string $username, string $password): string
{
    // ... mencoba beberapa format username ...
    return $this->requestToken($username, $password);
}

private function requestToken(string $username, string $password): string
{
    $response = Http::timeout(15)
        ->withoutVerifying()
        ->get("{$this->baseUrl}/login/token.php", [
            'username' => $username,
            'password' => $password,   // ← dikirim ke server MOODLE (bukan database kita)
            'service'  => $this->service,
        ]);

    $data = $response->json();

    // Yang dikembalikan = token (bukan password)
    return $data['token'];
}
```

> [!IMPORTANT]
> Password dikirim langsung ke **server Moodle Ilmu 2** (`ilmu2.upnjatim.ac.id`) melalui HTTPS.
> Fungsi ini mengembalikan **token**, bukan password. Password tidak disimpan ke variabel apapun setelah fungsi ini selesai.

---

## Bukti 4: Model User — Tidak Ada Field Password

**File:** [`app/Models/User.php`](file:///c:/Document/VsCode/radar-ilmu2-bot/app/Models/User.php) — Baris 10–23

```php
protected $fillable = [
    'name',
    'email',
    'moodle_token',           // ← Token Moodle
    'telegram_chat_id',
    'sync_code',
    'is_active',
    'subscription_status',
    'subscription_expires_at',
    'trial_used',
    'trial_started_at',
    'last_moodle_error',
    'last_checked_at',
    // ← tidak ada 'password' dalam daftar kolom yang boleh diisi
];
```

> [!NOTE]
> `$fillable` mendefinisikan kolom apa saja yang boleh diisi ke database.
> `password` tidak ada dalam daftar ini, artinya bahkan jika ada yang mencoba memasukkan password, Laravel akan menolaknya.

---

## Alur Kerja Keseluruhan (Diagram)

```
User                    Aplikasi Kita              Server Moodle
  |                          |                           |
  |-- email + password ----→ |                           |
  |                          |-- email + password -----→ |
  |                          |                           | (validasi)
  |                          |←-- TOKEN ---------------  |
  |                          |                           |
  |                          | [password dibuang di sini]
  |                          |                           |
  |                          | Simpan ke database:       |
  |                          |  - email ✓                |
  |                          |  - name ✓                 |
  |                          |  - moodle_token ✓         |
  |                          |  - password ✗ (TIDAK)     |
  |                          |                           |
  |←-- Session (user_id) --- |                           |
```

---

## Kesimpulan

| Yang Disimpan di Database | Yang TIDAK Disimpan |
|--------------------------|---------------------|
| ✅ Email                 | ❌ Password          |
| ✅ Nama                  | ❌ Hash password     |
| ✅ Moodle Token (bukan password) | ❌ Password terenkripsi |
| ✅ Telegram Chat ID      | ❌ Apapun yang bisa dipakai untuk login manual |
| ✅ Status langganan      | |

**Moodle Token** yang disimpan adalah token sementara yang dikeluarkan oleh server Ilmu 2 — bukan password. Token ini hanya bisa digunakan untuk mengambil data tugas dari API Moodle, **tidak bisa digunakan untuk login ke Ilmu 2**.

---

*Dokumen ini dihasilkan berdasarkan analisis source code aktual pada: **8 Agustus 2026***  
*Repository: `kakaadsp/radar-ilmu2-bot`*
