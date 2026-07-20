<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Radar Ilmu 2 — Bot Telegram yang otomatis memantau tugas Moodle UPN Jatim dan mengirim pengingat H-7, H-1 langsung ke HP kamu.">
    <title>Radar Ilmu 2 — Jangan Sampai Telat Kumpul Tugas</title>
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
                    colors: {
                        green: {
                            50:  '#f0fdf4',
                            100: '#dcfce7',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#198754',
                            700: '#15803d',
                            800: '#0B7A3A',
                            900: '#064e27',
                        },
                        yellow: {
                            300: '#FFE080',
                            400: '#FFD447',
                            500: '#F7C948',
                        },
                        surface: {
                            DEFAULT: '#0a0a0a',
                            50:  '#111111',
                            100: '#161616',
                            200: '#1c1c1c',
                            300: '#232323',
                            400: '#2a2a2a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0a0a0a;
            color: #fff;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Noise texture overlay ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
            opacity: 0.4;
        }

        /* ── Gradient glow ── */
        .hero-glow {
            position: absolute;
            top: -30%;
            left: 50%;
            transform: translateX(-50%);
            width: 900px;
            height: 600px;
            background: radial-gradient(ellipse at center, rgba(25,135,84,0.18) 0%, rgba(25,135,84,0.06) 45%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* ── Glass card ── */
        .card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            transition: border-color 0.2s, background 0.2s;
        }
        .card:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.12);
        }

        /* ── Gradient text ── */
        .grad { background: linear-gradient(135deg, #22c55e 0%, #FFD447 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .grad-green { background: linear-gradient(135deg, #22c55e, #198754); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /* ── Buttons ── */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, #22c55e, #198754);
            color: #fff; font-weight: 700; font-size: 0.9rem;
            padding: 12px 24px; border-radius: 12px;
            border: none; cursor: pointer; text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 0 0 1px rgba(34,197,94,0.3), 0 4px 16px rgba(25,135,84,0.2);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 0 0 1px rgba(34,197,94,0.5), 0 8px 24px rgba(25,135,84,0.35);
        }
        .btn-outline {
            display: inline-flex; align-items: center; gap: 8px;
            background: transparent;
            color: rgba(255,255,255,0.8); font-weight: 600; font-size: 0.875rem;
            padding: 11px 22px; border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.12); cursor: pointer;
            text-decoration: none; transition: all 0.2s ease;
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.2);
            color: #fff;
        }

        /* ── Navbar ── */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(10,10,10,0.8);
        }

        /* ── Divider ── */
        .divider { border: none; height: 1px; background: rgba(255,255,255,0.06); margin: 0; }

        /* ── Feature number ── */
        .feat-num {
            font-size: 0.65rem; font-weight: 800; letter-spacing: 0.08em;
            color: rgba(255,255,255,0.25); text-transform: uppercase;
        }

        /* ── Notification previews ── */
        .notif-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex; align-items: flex-start; gap: 12px;
        }
        .notif-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 16px;
        }

        /* ── Fade-in animation ── */
        @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        @keyframes float { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-6px); } }
        .fade-up { opacity:0; animation: fadeUp 0.6s ease forwards; }
        .float-1 { animation: float 5s ease-in-out infinite; }
        .float-2 { animation: float 5s ease-in-out infinite 1.5s; }
        .float-3 { animation: float 5s ease-in-out infinite 3s; }

        /* ── Scroll reveal ── */
        .reveal { opacity:0; transform:translateY(24px); transition: opacity 0.5s ease, transform 0.5s ease; }
        .reveal.in { opacity:1; transform:translateY(0); }

        /* ── Pricing border glow ── */
        .pricing-pro { border-color: rgba(34,197,94,0.4); box-shadow: 0 0 40px rgba(25,135,84,0.12); }

        /* ── Badge ── */
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2);
            color: #4ade80; font-size: 0.75rem; font-weight: 600;
            padding: 5px 12px; border-radius: 999px;
        }
        .badge-dot { width:6px; height:6px; border-radius:50%; background:#22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.4;} }

        /* ── Step line ── */
        .step-connector { width:1px; height:32px; background:linear-gradient(to bottom,rgba(34,197,94,0.3),transparent); margin:0 auto; }
    </style>
</head>
<body>

<!-- ── NAVBAR ────────────────────────────────────────────── -->
<nav class="navbar">
    <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2.5 text-sm font-bold tracking-tight">
            <img src="/images/mascot.png" alt="Mascot Logo" class="w-7 h-7 object-contain rounded-lg">
            <span>Radar<span class="grad-green">Ilmu 2</span></span>
        </a>
        <div class="hidden md:flex items-center gap-7 text-sm font-medium text-white/50">
            <a href="#fitur"     class="hover:text-white transition-colors">Fitur</a>
            <a href="#cara-kerja" class="hover:text-white transition-colors">Cara Kerja</a>
            <a href="#harga"    class="hover:text-white transition-colors">Harga</a>
        </div>
        <a href="{{ route('login.show') }}" class="btn-primary text-sm px-5 py-2.5">
            Mulai Gratis <span class="opacity-70">→</span>
        </a>
    </div>
</nav>

<!-- ── HERO ─────────────────────────────────────────────── -->
<section class="relative min-h-screen flex flex-col items-center justify-center pt-14 pb-24 overflow-hidden">
    <div class="hero-glow"></div>

    <div class="relative z-10 max-w-6xl mx-auto px-6 w-full">
        <div class="grid lg:grid-cols-2 gap-16 items-center">

            <!-- Left copy -->
            <div class="space-y-7">
                <div class="badge fade-up" style="animation-delay:.1s">
                    <span class="badge-dot"></span>
                    Aktif memantau Moodle UPN Jatim
                </div>

                <h1 class="text-5xl sm:text-6xl font-black leading-[1.08] tracking-tight fade-up" style="animation-delay:.2s">
                    Jangan<br>
                    Sampai <span class="grad">Telat</span><br>
                    Kumpul Tugas.
                </h1>

                <p class="text-white/55 text-lg leading-relaxed max-w-md fade-up" style="animation-delay:.3s">
                    Bot Telegram yang memantau <strong class="text-white/80">Moodle Ilmu 2</strong> secara otomatis dan mengirim pengingat tugas langsung ke HP kamu — H-7, H-1, sampai notifikasi tugas baru.
                </p>

                <div class="flex flex-wrap items-center gap-3 fade-up" style="animation-delay:.4s">
                    <a href="{{ route('login.show') }}" class="btn-primary">
                        Coba Gratis 7 Hari
                    </a>
                    <a href="#cara-kerja" class="btn-outline">
                        Lihat cara kerja
                    </a>
                </div>

                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-white/40 fade-up" style="animation-delay:.5s">
                    <span class="flex items-center gap-1.5"><span class="text-green-500">✓</span> Gratis 7 hari pertama</span>
                    <span class="flex items-center gap-1.5"><span class="text-green-500">✓</span> Anti-spam, H-7 & H-1 saja</span>
                    <span class="flex items-center gap-1.5"><span class="text-green-500">✓</span> Password tidak disimpan</span>
                </div>
            </div>

            <!-- Right: Notification previews -->
            <div class="hidden lg:block relative pl-8 fade-up" style="animation-delay:.35s">
                <!-- Glow behind cards -->
                <div class="absolute inset-4 bg-green-600/8 rounded-3xl blur-3xl pointer-events-none"></div>

                <div class="space-y-3 relative z-10">
                    <!-- Card 1 -->
                    <div class="notif-card float-1">
                        <div class="notif-icon bg-green-600/20">🔔</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-green-400 text-[10px] font-bold tracking-widest uppercase mb-1">Tugas Baru dari Dosen</div>
                            <div class="text-sm font-semibold text-white">Pemrograman Berbasis Web</div>
                            <div class="text-xs text-white/40 mt-0.5">📝 Implementasi MVC · Deadline: 30 Jul 2026</div>
                        </div>
                        <div class="text-[10px] text-white/30 flex-shrink-0 mt-0.5">baru saja</div>
                    </div>

                    <!-- Card 2 -->
                    <div class="notif-card float-2">
                        <div class="notif-icon bg-yellow-400/15">🚨</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-yellow-400 text-[10px] font-bold tracking-widest uppercase mb-1">Pengingat H-7</div>
                            <div class="text-sm font-semibold text-white">Analisis & Desain SI</div>
                            <div class="text-xs text-white/40 mt-0.5">⏳ 7 hari lagi — mulai nyicil sekarang!</div>
                        </div>
                        <div class="text-[10px] text-white/30 flex-shrink-0 mt-0.5">08:05</div>
                    </div>

                    <!-- Card 3 -->
                    <div class="notif-card float-3" style="border-color:rgba(239,68,68,0.2)">
                        <div class="notif-icon bg-red-500/15">⚠️</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-red-400 text-[10px] font-bold tracking-widest uppercase mb-1">Deadline Besok! H-1</div>
                            <div class="text-sm font-semibold text-white">Basis Data — Tugas Akhir</div>
                            <div class="text-xs text-red-400/70 mt-0.5">🔥 Kurang dari 24 jam — kerjain sekarang!</div>
                        </div>
                        <div class="text-[10px] text-white/30 flex-shrink-0 mt-0.5">08:05</div>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-3 pt-1">
                        @foreach([['2.4K','Target User'],['0','Tugas Terlewat'],['30m','Check Rate']] as [$val,$label])
                        <div class="card p-4 text-center">
                            <div class="text-xl font-black grad-green">{{ $val }}</div>
                            <div class="text-[11px] text-white/35 mt-1">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<hr class="divider">

<!-- ── FITUR ─────────────────────────────────────────────── -->
<section id="fitur" class="py-28">
    <div class="max-w-6xl mx-auto px-6">

        <div class="mb-16 reveal">
            <div class="text-green-500 text-xs font-bold tracking-widest uppercase mb-3">Fitur</div>
            <h2 class="text-3xl sm:text-4xl font-black leading-tight">Dibuat untuk mahasiswa<br><span class="text-white/40">yang selalu sibuk.</span></h2>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
            $features = [
                ['📡','Radar Real-Time','Tugas baru terdeteksi dalam 30 menit setelah dosen posting. Kamu yang pertama tahu.'],
                ['🛡️','Anti-Spam','H-7 untuk planning, H-1 untuk darurat. Tidak ada notifikasi per jam yang mengganggu.'],
                ['🔐','Password Aman','Password hanya dipakai sekali untuk ambil token Moodle. Tidak pernah disimpan ke server.'],
                ['⚡','Direct Link','Satu tap dari notifikasi langsung ke halaman pengumpulan tugas di Moodle. Zero friction.'],
                ['⏸','Pause Kapan Saja','Libur? Matikan notifikasi dengan /pause. Hidupkan lagi kapan pun kamu mau.'],
                ['💸','Rp 5.000/bulan','Lebih murah dari kopi kampus. Dan 7 hari pertama gratis tanpa syarat apapun.'],
            ];
            @endphp

            @foreach($features as $i => [$icon,$title,$desc])
            <div class="card p-6 reveal" style="transition-delay:{{ $i * 0.07 }}s">
                <div class="text-2xl mb-4">{{ $icon }}</div>
                <div class="font-bold text-sm mb-2">{{ $title }}</div>
                <div class="text-white/45 text-sm leading-relaxed">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<hr class="divider">

<!-- ── CARA KERJA ────────────────────────────────────────── -->
<section id="cara-kerja" class="py-28">
    <div class="max-w-2xl mx-auto px-6">

        <div class="mb-16 reveal">
            <div class="text-green-500 text-xs font-bold tracking-widest uppercase mb-3">Cara Kerja</div>
            <h2 class="text-3xl sm:text-4xl font-black">Setup dalam<br><span class="grad-green">2 menit.</span></h2>
        </div>

        <div class="space-y-0">
            @php
            $steps = [
                ['Login akun Ilmu 2','Masukkan email & password UPN Jatim kamu. Sistem mengambil token Moodle otomatis — password langsung dilupakan.'],
                ['Klik link Telegram','Website tampilkan link unik ke bot. Klik dan tekan START di Telegram.'],
                ['Akun tersambung','Bot konfirmasi. Moodle dan Telegram kamu tersinkronisasi dalam hitungan detik.'],
                ['Terima notifikasi otomatis','Setiap 30 menit sistem pantau Moodle-mu. Tugas baru atau deadline dekat? HP langsung bunyi.'],
            ];
            @endphp

            @foreach($steps as $i => [$title, $desc])
            <div class="reveal" style="transition-delay:{{ $i * 0.1 }}s">
                <div class="flex gap-5">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full border border-green-600/50 flex items-center justify-center text-xs font-black text-green-500 flex-shrink-0">
                            {{ $i + 1 }}
                        </div>
                        @if(!$loop->last)
                        <div class="step-connector"></div>
                        @endif
                    </div>
                    <div class="pb-8 pt-1">
                        <div class="font-bold text-sm mb-1">{{ $title }}</div>
                        <div class="text-white/45 text-sm leading-relaxed">{{ $desc }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<hr class="divider">

<!-- ── HARGA ─────────────────────────────────────────────── -->
<section id="harga" class="py-28">
    <div class="max-w-3xl mx-auto px-6">

        <div class="mb-16 text-center reveal">
            <div class="text-green-500 text-xs font-bold tracking-widest uppercase mb-3">Harga</div>
            <h2 class="text-3xl sm:text-4xl font-black">Lebih murah dari<br><span class="grad-green">kopi kampus.</span></h2>
        </div>

        <div class="grid sm:grid-cols-2 gap-5">

            <!-- Free -->
            <div class="card p-7 reveal">
                <div class="text-xs font-bold text-white/30 uppercase tracking-widest mb-5">Free Trial</div>
                <div class="text-4xl font-black mb-1">Rp 0</div>
                <div class="text-white/35 text-sm mb-7">7 hari pertama</div>
                <div class="space-y-3 mb-8">
                    @foreach(['Semua fitur notifikasi','Unlimited tugas','H-7 & H-1 reminders','Notifikasi tugas baru'] as $f)
                    <div class="flex items-center gap-2.5 text-sm text-white/60">
                        <span class="text-green-500 text-xs">✓</span> {{ $f }}
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('login.show') }}" class="btn-outline w-full justify-center">
                    Mulai Gratis
                </a>
            </div>

            <!-- Pro -->
            <div class="card pricing-pro p-7 reveal relative overflow-hidden" style="transition-delay:.1s">
                <div class="absolute top-4 right-4 bg-green-600 text-white text-[10px] font-black px-2.5 py-1 rounded-full uppercase tracking-wide">Populer</div>
                <div class="text-xs font-bold text-white/30 uppercase tracking-widest mb-5">Pro</div>
                <div class="text-4xl font-black mb-1">Rp 5.000</div>
                <div class="text-white/35 text-sm mb-7">per bulan</div>
                <div class="space-y-3 mb-8">
                    @foreach(['Semua fitur Free Trial','Notifikasi sepanjang semester','Auto-deteksi token kedaluwarsa','Support via Telegram'] as $f)
                    <div class="flex items-center gap-2.5 text-sm text-white/60">
                        <span class="text-green-500 text-xs">✓</span> {{ $f }}
                    </div>
                    @endforeach
                </div>
                <a href="{{ route('login.show') }}" class="btn-primary w-full justify-center">
                    Berlangganan Sekarang
                </a>
            </div>

        </div>
    </div>
</section>

<hr class="divider">

<!-- ── FOOTER ────────────────────────────────────────────── -->
<footer class="py-12">
    <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-sm font-bold">
            <img src="/images/mascot.png" alt="Mascot Logo" class="w-6 h-6 object-contain rounded-md">
            <span>Radar<span class="grad-green">Ilmu 2</span></span>
        </div>
        <div class="text-white/25 text-xs text-center">
            © {{ date('Y') }} Radar Ilmu 2 · Dibuat untuk mahasiswa UPN Jatim · Tidak berafiliasi resmi dengan universitas.
        </div>
        <div class="text-white/25 text-xs">
            Rp 5.000/bulan
        </div>
    </div>
</footer>

<script>
    // Scroll reveal
    const io = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));
</script>
</body>
</html>
