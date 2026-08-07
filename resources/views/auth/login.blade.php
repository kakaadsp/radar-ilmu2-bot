<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Radar Ilmu 2</title>
    <link rel="icon" type="image/png" href="/images/mascot.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0a0a0a;
            color: #fff;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* Subtle grid background */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none; z-index: 0;
        }

        /* Glow */
        .glow {
            position: fixed; top: 0; left: 50%; transform: translateX(-50%);
            width: 600px; height: 400px;
            background: radial-gradient(ellipse at 50% 0%, rgba(25,135,84,0.2) 0%, transparent 65%);
            pointer-events: none; z-index: 0;
        }

        /* Card */
        .login-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 36px;
        }

        /* Input */
        .input-wrap { position: relative; }
        .input-field {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
            font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: border-color 0.2s, background 0.2s;
            outline: none;
        }
        .input-field::placeholder { color: rgba(255,255,255,0.25); }
        .input-field:focus {
            border-color: rgba(34,197,94,0.5);
            background: rgba(34,197,94,0.04);
            box-shadow: 0 0 0 3px rgba(34,197,94,0.1);
        }
        .input-field.error {
            border-color: rgba(239,68,68,0.5);
        }

        /* Label */
        .label { font-size: 0.8rem; font-weight: 600; color: rgba(255,255,255,0.55); margin-bottom: 6px; display: block; }

        /* Button */
        .btn-submit {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #22c55e, #198754);
            border: none; border-radius: 12px;
            color: #fff; font-weight: 700; font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(25,135,84,0.25);
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(25,135,84,0.4);
        }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* Error box */
        .err-box {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 10px;
            padding: 12px 14px;
            color: #fca5a5;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        /* Success box */
        .success-box {
            background: rgba(34,197,94,0.06);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 16px;
            padding: 28px;
            text-align: center;
        }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.7s linear infinite; }

        /* Fade in */
        @keyframes fadeIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeIn 0.4s ease forwards; }

        /* Toggle eye */
        .eye-btn { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.3); font-size: 14px; padding: 0; transition: color 0.15s; }
        .eye-btn:hover { color: rgba(255,255,255,0.6); }

        /* Telegram link button */
        .tg-btn {
            display: flex; align-items: center; justify-content: center; gap-10px;
            gap: 10px;
            background: #229ED9; color: #fff;
            border-radius: 12px; padding: 13px;
            font-weight: 700; font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 4px 16px rgba(34,158,217,0.25);
        }
        .tg-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(34,158,217,0.4); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="glow"></div>

    <div class="relative z-10 w-full max-w-md fade-in">

        <!-- Back link -->
        <div class="mb-6">
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-sm text-white/35 hover:text-white/70 transition-colors">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Kembali ke beranda
            </a>
        </div>

        <div class="login-card">

            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-2.5 mb-6">
                    <img src="/images/mascot.png" alt="Radar Ilmu 2 Logo" class="w-8 h-8 rounded-xl object-contain" onerror="this.style.display='none'">
                    <span class="font-bold text-sm">
                        Radar<span style="background:linear-gradient(135deg,#22c55e,#198754);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Ilmu 2</span>
                    </span>
                </div>
                <h1 class="text-2xl font-black tracking-tight">Masuk ke akun kamu</h1>
                <p class="text-white/40 text-sm mt-2 leading-relaxed">
                    Gunakan email & password Ilmu 2 UPN Jatim kamu.<br>
                    <strong class="text-white/60">Password tidak disimpan</strong> — hanya dipakai untuk ambil token.
                </p>
            </div>

            <!-- Form -->
            <div id="formArea">
                <form id="loginForm" class="space-y-4" novalidate>
                    @csrf

                    <!-- Email -->
                    <div>
                        <label class="label" for="email">Email UPN Jatim</label>
                        <div class="input-wrap">
                            <input
                                type="email" id="email" name="email"
                                placeholder="24082010xxx@student.upnjatim.ac.id"
                                class="input-field"
                                autocomplete="email"
                                required
                            >
                        </div>
                        <p id="emailErr" class="hidden text-red-400 text-xs mt-1.5">Gunakan email UPN Jatim (@student.upnjatim.ac.id atau @upnjatim.ac.id)</p>
                    </div>


                    <!-- Password -->
                    <div>
                        <label class="label" for="password">Password Ilmu 2</label>
                        <div class="input-wrap">
                            <input
                                type="password" id="password" name="password"
                                placeholder="Password akun Ilmu 2 kamu"
                                class="input-field" style="padding-right: 44px;"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="eye-btn" id="eyeBtn" aria-label="Tampilkan password">
                                <svg id="eyeShow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="eyeHide" class="hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Error message -->
                    <div id="formErr" class="hidden err-box"></div>

                    <!-- Submit -->
                    <button type="submit" id="submitBtn" class="btn-submit mt-2">
                        <span id="btnLabel">Lanjutkan</span>
                        <div id="btnSpinner" class="spinner hidden"></div>
                    </button>
                </form>

                <!-- Divider -->
                <div class="flex items-center gap-3 my-5">
                    <div class="flex-1 h-px bg-white/6"></div>
                    <span class="text-white/20 text-xs">atau</span>
                    <div class="flex-1 h-px bg-white/6"></div>
                </div>

                <!-- Security note -->
                <div class="rounded-xl p-4 text-xs text-white/30 leading-relaxed" style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);">
                    🔒 <strong class="text-white/40">Keamanan data kamu terjamin.</strong> Sistem kami hanya menggunakan email & password untuk mengambil <em>token API</em> dari server Ilmu 2 — sama persis seperti saat kamu login via aplikasi mobile Moodle. Setelah token didapat, password langsung dilupakan.
                </div>
            </div>

            <!-- SUCCESS STATE (hidden initially) -->
            <div id="successArea" class="hidden">
                <div class="success-box">
                    <div class="text-4xl mb-4">🎉</div>
                    <h2 class="font-black text-xl mb-2">Berhasil!</h2>
                    <p class="text-white/50 text-sm mb-6" id="successMsg">
                        Akun Moodle kamu berhasil terdeteksi. Sekarang hubungkan Telegram kamu untuk mulai menerima notifikasi.
                    </p>
                    <a id="tgLink" href="#" target="_blank" class="tg-btn">
                        ✈️ &nbsp;Buka Telegram & Klik START
                    </a>
                    <p class="text-white/25 text-xs mt-4">Bot akan merespons otomatis dan akunmu langsung terhubung.</p>
                </div>

                <div class="mt-5 text-center">
                    <a href="{{ route('dashboard') }}" class="text-sm text-white/40 hover:text-white/70 transition-colors">
                        Sudah terhubung? Buka dashboard →
                    </a>
                </div>
            </div>

        </div>

        <!-- Footer note -->
        <p class="text-center text-white/20 text-xs mt-6">
            Dengan masuk, kamu menyetujui bahwa data token Moodle kamu disimpan secara aman untuk keperluan notifikasi.
        </p>

    </div>

    <script>
        // ── Toggle password visibility ──────────────────────────────────
        const eyeBtn  = document.getElementById('eyeBtn');
        const eyeShow = document.getElementById('eyeShow');
        const eyeHide = document.getElementById('eyeHide');
        const pwField = document.getElementById('password');

        eyeBtn.addEventListener('click', () => {
            const show = pwField.type === 'password';
            pwField.type = show ? 'text' : 'password';
            eyeShow.classList.toggle('hidden', show);
            eyeHide.classList.toggle('hidden', !show);
        });

        // ── Form submit ─────────────────────────────────────────────────
        const form      = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnLabel  = document.getElementById('btnLabel');
        const btnSpinner= document.getElementById('btnSpinner');
        const formErr   = document.getElementById('formErr');
        const emailInput= document.getElementById('email');
        const emailErr  = document.getElementById('emailErr');

        function isUpnEmail(email) {
            const val = email.toLowerCase().trim();
            return val.endsWith('@upnjatim.ac.id') || val.endsWith('@student.upnjatim.ac.id');
        }

        // Live email validation
        emailInput.addEventListener('blur', () => {
            const val = emailInput.value.trim();
            const ok  = isUpnEmail(val) || val === '';
            emailErr.classList.toggle('hidden', ok);
            emailInput.classList.toggle('error', !ok && val !== '');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            formErr.classList.add('hidden');

            // Validate email domain
            const email = emailInput.value.trim();
            if (!isUpnEmail(email)) {
                emailErr.classList.remove('hidden');
                emailInput.classList.add('error');
                emailInput.focus();
                return;
            }

            // Loading state
            setLoading(true);

            try {
                const res = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email,
                        password: document.getElementById('password').value,
                        _token: '{{ csrf_token() }}'
                    })
                });

                const data = await res.json();

                if (data.success) {
                    showSuccess(data);
                } else {
                    showError(data.message || 'Terjadi kesalahan. Coba lagi.');
                }
            } catch {
                showError('Koneksi gagal. Periksa koneksi internet kamu dan coba lagi.');
            } finally {
                setLoading(false);
            }
        });

        function setLoading(on) {
            submitBtn.disabled = on;
            btnLabel.textContent = on ? 'Memproses...' : 'Lanjutkan';
            btnSpinner.classList.toggle('hidden', !on);
        }

        function showError(msg) {
            formErr.textContent = msg;
            formErr.classList.remove('hidden');
            // Shake animation
            formErr.animate([{transform:'translateX(-4px)'},{transform:'translateX(4px)'},{transform:'translateX(0)'}], {duration:300});
        }

        function showSuccess(data) {
            document.getElementById('formArea').classList.add('hidden');
            const successArea = document.getElementById('successArea');
            successArea.classList.remove('hidden');

            document.getElementById('successMsg').textContent =
                `Halo ${data.user_name}! Token Moodle berhasil diambil. Klik tombol di bawah untuk menghubungkan Telegram kamu.`;

            const deepLink = `https://t.me/${data.bot_username}?start=${data.sync_code}`;
            document.getElementById('tgLink').href = deepLink;
        }
    </script>
</body>
</html>
