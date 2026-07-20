<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Radar Ilmu 2</title>
    <link rel="icon" type="image/png" href="/images/mascot.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        primary:   { DEFAULT: '#198754', dark: '#0B7A3A', light: '#28a745', xlight: '#d4edda' },
                        secondary: { DEFAULT: '#FFD447', dark: '#F7C948' },
                        dark:      { DEFAULT: '#0d1117', card: '#161b22', border: '#21262d' },
                    },
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(22,27,34,0.9); backdrop-filter: blur(16px); border: 1px solid rgba(33,38,45,0.8); }
        .gradient-text { background: linear-gradient(135deg, #198754, #FFD447); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .card-hover { transition: transform 0.2s, box-shadow 0.2s; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(25,135,84,0.1); }
        .status-dot { width:8px; height:8px; border-radius:50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.5; } }
        .progress-bar { background: linear-gradient(90deg, #198754, #FFD447); border-radius: 9999px; height: 6px; }
        .skeleton { background: linear-gradient(90deg, #161b22 25%, #21262d 50%, #161b22 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .deadline-urgent { border-left: 3px solid #ef4444; }
        .deadline-warning { border-left: 3px solid #FFD447; }
        .deadline-safe { border-left: 3px solid #198754; }
        .sidebar { transition: transform 0.3s ease; }
    </style>
</head>
<body class="bg-dark text-white antialiased min-h-screen">

    <div class="flex min-h-screen">
        <!-- ── Sidebar ──────────────────────────────────────────────────── -->
        <aside class="w-64 glass border-r border-dark-border flex-shrink-0 hidden md:flex flex-col">
            <div class="p-6 border-b border-dark-border">
                <div class="flex items-center gap-3">
                    <img src="/images/mascot.png" alt="Mascot" class="w-10 h-10 rounded-xl" onerror="this.style.display='none'">
                    <div>
                        <div class="font-bold"><span class="text-white">Radar</span><span class="gradient-text">Ilmu 2</span></div>
                        <div class="text-xs text-gray-500">Dashboard</div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-primary/10 text-primary text-sm font-medium">
                    <span>📊</span> Overview
                </a>
                <a href="#tasks" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-dark-card text-sm font-medium transition-colors">
                    <span>📚</span> Tugas
                </a>
                <a href="#subscription" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-dark-card text-sm font-medium transition-colors">
                    <span>💳</span> Langganan
                </a>
                <a href="#settings" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-dark-card text-sm font-medium transition-colors">
                    <span>⚙️</span> Pengaturan
                </a>
            </nav>

            <div class="p-4 border-t border-dark-border">
                <a href="{{ route('logout') }}" class="flex items-center gap-2 px-3 py-2 rounded-xl text-gray-400 hover:text-red-400 text-sm transition-colors">
                    <span>🚪</span> Keluar
                </a>
            </div>
        </aside>

        <!-- ── Main Content ─────────────────────────────────────────────── -->
        <main class="flex-1 overflow-auto">
            <!-- Top bar (mobile) -->
            <header class="glass border-b border-dark-border px-6 py-4 flex items-center justify-between md:hidden sticky top-0 z-40">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <img src="/images/mascot.png" alt="Radar Ilmu 2 Logo" class="w-8 h-8 rounded-lg object-contain" onerror="this.style.display='none'">
                    <span class="font-bold"><span class="text-white">Radar</span><span class="gradient-text">Ilmu 2</span></span>
                </a>
                <a href="{{ route('logout') }}" class="text-xs text-gray-400">Keluar</a>
            </header>

            <div class="max-w-6xl mx-auto p-6 space-y-6">

                <!-- ── Welcome Banner ───────────────────────────────────── -->
                <div class="glass rounded-2xl p-6 border border-primary/20 bg-gradient-to-r from-primary/5 to-transparent">
                    <div class="flex items-start justify-between flex-wrap gap-4">
                        <div>
                            <h1 class="text-2xl font-extrabold">
                                Halo, <span class="gradient-text">{{ $user->name }}</span>! 👋
                            </h1>
                            <p class="text-gray-400 text-sm mt-1">
                                {{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }} · Sistem aktif memantau Moodle kamu.
                            </p>
                        </div>
                        @if($user->hasActiveAccess())
                            @if($user->subscription_status)
                                <span class="inline-flex items-center gap-2 bg-primary/20 border border-primary/40 text-primary text-xs font-semibold px-3 py-1.5 rounded-full">
                                    <span class="status-dot bg-primary"></span>
                                    Pro · Aktif hingga {{ $user->subscription_expires_at->locale('id')->isoFormat('D MMM YYYY') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 bg-secondary/10 border border-secondary/40 text-secondary text-xs font-semibold px-3 py-1.5 rounded-full">
                                    <span class="status-dot bg-secondary"></span>
                                    Free Trial
                                    @if($user->getTrialExpiresAt())
                                        · Sampai {{ $user->getTrialExpiresAt()->locale('id')->isoFormat('D MMM YYYY') }}
                                    @endif
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center gap-2 bg-red-500/10 border border-red-500/40 text-red-400 text-xs font-semibold px-3 py-1.5 rounded-full">
                                <span class="status-dot bg-red-500"></span>
                                Akses Berakhir
                            </span>
                        @endif
                    </div>
                </div>

                <!-- ── Status Cards ─────────────────────────────────────── -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @php
                    $activeTasks   = $user->taskReminders->count();
                    $urgentTasks   = $user->taskReminders->filter(fn($t) => $t->getDaysRemaining() <= 1)->count();
                    $weekTasks     = $user->taskReminders->filter(fn($t) => $t->getDaysRemaining() <= 7)->count();
                    $moodleStatus  = $user->hasMoodleToken();
                    $telegramLinked = $user->hasTelegramLinked();
                    @endphp

                    <div class="glass rounded-2xl p-5 border border-dark-border card-hover">
                        <div class="text-2xl mb-2">📚</div>
                        <div class="text-2xl font-black text-white">{{ $activeTasks }}</div>
                        <div class="text-xs text-gray-400 mt-1">Tugas Aktif</div>
                    </div>
                    <div class="glass rounded-2xl p-5 border {{ $urgentTasks > 0 ? 'border-red-500/40' : 'border-dark-border' }} card-hover">
                        <div class="text-2xl mb-2">⚠️</div>
                        <div class="text-2xl font-black {{ $urgentTasks > 0 ? 'text-red-400' : 'text-white' }}">{{ $urgentTasks }}</div>
                        <div class="text-xs text-gray-400 mt-1">Deadline H-1</div>
                    </div>
                    <div class="glass rounded-2xl p-5 border {{ $weekTasks > 0 ? 'border-secondary/40' : 'border-dark-border' }} card-hover">
                        <div class="text-2xl mb-2">🗓</div>
                        <div class="text-2xl font-black {{ $weekTasks > 0 ? 'text-secondary' : 'text-white' }}">{{ $weekTasks }}</div>
                        <div class="text-xs text-gray-400 mt-1">Deadline 7 Hari</div>
                    </div>
                    <div class="glass rounded-2xl p-5 border {{ $moodleStatus && $telegramLinked ? 'border-primary/40' : 'border-red-500/40' }} card-hover">
                        <div class="text-2xl mb-2">🔗</div>
                        <div class="text-2xl font-black {{ $moodleStatus && $telegramLinked ? 'text-primary' : 'text-red-400' }}">
                            {{ $moodleStatus && $telegramLinked ? 'OK' : '!' }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Koneksi</div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- ── Tasks List ───────────────────────────────────── -->
                    <div class="lg:col-span-2 space-y-4" id="tasks">
                        <div class="flex items-center justify-between">
                            <h2 class="font-bold text-lg">📋 Tugas Aktif</h2>
                            <span class="text-xs text-gray-500">Update tiap 30 menit</span>
                        </div>

                        @if($user->taskReminders->isEmpty())
                            <div class="glass rounded-2xl p-12 border border-dark-border text-center">
                                <div class="text-5xl mb-4">🎉</div>
                                <h3 class="font-bold text-lg">Belum ada tugas terdeteksi</h3>
                                <p class="text-gray-400 text-sm mt-2 max-w-xs mx-auto">
                                    Radar sedang aktif memantau Moodle. Tugas baru akan muncul di sini otomatis dalam 30 menit ke depan.
                                </p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($user->taskReminders as $task)
                                    @php
                                        $daysLeft = $task->getDaysRemaining();
                                        $urgencyClass = $daysLeft <= 1 ? 'deadline-urgent' : ($daysLeft <= 7 ? 'deadline-warning' : 'deadline-safe');
                                        $badgeClass   = $daysLeft <= 1 ? 'bg-red-500/20 text-red-400 border-red-500/30' : ($daysLeft <= 7 ? 'bg-secondary/10 text-secondary border-secondary/30' : 'bg-primary/10 text-primary border-primary/30');
                                    @endphp
                                    <div class="glass rounded-xl p-4 border border-dark-border card-hover {{ $urgencyClass }} pl-5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="text-xs text-gray-500 mb-1">{{ $task->course_name ?: $task->course_fullname }}</div>
                                                <div class="font-semibold text-sm text-white truncate">{{ $task->task_name }}</div>
                                                <div class="text-xs text-gray-400 mt-1">
                                                    🗓 {{ $task->deadline->locale('id')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                                <span class="text-xs font-bold px-2 py-1 rounded-full border {{ $badgeClass }}">
                                                    {{ $daysLeft <= 0 ? 'Terlambat' : "H-{$daysLeft}" }}
                                                </span>
                                                @if($task->moodle_url)
                                                <a href="{{ $task->moodle_url }}" target="_blank" class="text-xs text-primary hover:underline">Buka →</a>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- Reminder status badges -->
                                        <div class="flex gap-2 mt-3">
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $task->new_task_sent ? 'bg-primary/20 text-primary' : 'bg-dark-card text-gray-600' }}">
                                                {{ $task->new_task_sent ? '✓' : '○' }} Baru
                                            </span>
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $task->h_7_sent ? 'bg-primary/20 text-primary' : 'bg-dark-card text-gray-600' }}">
                                                {{ $task->h_7_sent ? '✓' : '○' }} H-7
                                            </span>
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $task->h_1_sent ? 'bg-red-500/20 text-red-400' : 'bg-dark-card text-gray-600' }}">
                                                {{ $task->h_1_sent ? '✓' : '○' }} H-1
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- ── Right Sidebar ─────────────────────────────────── -->
                    <div class="space-y-4">
                        <!-- Connection Status -->
                        <div class="glass rounded-2xl p-5 border border-dark-border" id="settings">
                            <h3 class="font-bold text-sm mb-4">🔗 Status Koneksi</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Moodle Ilmu 2</span>
                                    <span class="flex items-center gap-1.5 text-xs font-medium {{ $moodleStatus ? 'text-primary' : 'text-red-400' }}">
                                        <span class="w-2 h-2 rounded-full {{ $moodleStatus ? 'bg-primary animate-pulse' : 'bg-red-400' }}"></span>
                                        {{ $moodleStatus ? 'Terhubung' : 'Tidak terhubung' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Telegram</span>
                                    <span class="flex items-center gap-1.5 text-xs font-medium {{ $telegramLinked ? 'text-primary' : 'text-yellow-400' }}">
                                        <span class="w-2 h-2 rounded-full {{ $telegramLinked ? 'bg-primary animate-pulse' : 'bg-yellow-400' }}"></span>
                                        {{ $telegramLinked ? 'Terhubung' : 'Belum' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Notifikasi</span>
                                    <span class="flex items-center gap-1.5 text-xs font-medium {{ $user->is_active ? 'text-primary' : 'text-gray-400' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Dijeda' }}
                                    </span>
                                </div>
                                @if($user->last_checked_at)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Cek terakhir</span>
                                    <span class="text-xs text-gray-300">{{ $user->last_checked_at->diffForHumans() }}</span>
                                </div>
                                @endif
                            </div>

                            @if(!$telegramLinked)
                            <div class="mt-4 p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-xl">
                                <p class="text-xs text-yellow-400 mb-2">Telegram belum terhubung. Hubungkan sekarang:</p>
                                <a href="https://t.me/{{ config('services.telegram.bot_username') }}?start={{ $syncCode }}"
                                   target="_blank"
                                   class="block text-center bg-secondary hover:bg-secondary-dark text-dark text-xs font-bold py-2 rounded-lg transition-all">
                                    ✈️ Buka Telegram
                                </a>
                            </div>
                            @endif

                            @if($user->last_moodle_error)
                            <div class="mt-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl">
                                <p class="text-xs text-red-400">⚠️ Error Moodle terakhir:<br>{{ $user->last_moodle_error }}</p>
                                @if($user->last_moodle_error === 'INVALID_TOKEN')
                                <a href="/" class="block mt-2 text-center bg-primary text-white text-xs font-bold py-2 rounded-lg">
                                    🔄 Login Ulang
                                </a>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Subscription Card -->
                        <div class="glass rounded-2xl p-5 border border-dark-border" id="subscription">
                            <h3 class="font-bold text-sm mb-4">💳 Langganan</h3>
                            @if($user->subscription_status && $user->subscription_expires_at)
                                <div class="space-y-2">
                                    <div class="text-xs text-gray-400">Masa aktif:</div>
                                    <div class="font-bold text-primary">{{ $user->subscription_expires_at->locale('id')->isoFormat('D MMMM YYYY') }}</div>
                                    <div class="text-xs text-gray-500">
                                        Sisa {{ $user->subscription_expires_at->diffInDays(now()) }} hari
                                    </div>
                                    @php $daysUntil = now()->diffInDays($user->subscription_expires_at, false); @endphp
                                    @if($daysUntil <= 3)
                                    <div class="p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-xl text-xs text-yellow-400">
                                        ⚠️ Langganan hampir habis! Perpanjang via bot Telegram.
                                    </div>
                                    @endif
                                </div>
                            @elseif(!$user->trial_used)
                                <div class="space-y-2">
                                    <div class="bg-secondary/10 border border-secondary/20 rounded-xl p-3">
                                        <div class="text-xs text-secondary font-semibold">🎁 Free Trial Aktif</div>
                                        @if($user->getTrialExpiresAt())
                                        <div class="text-xs text-gray-400 mt-1">
                                            Sampai: {{ $user->getTrialExpiresAt()->locale('id')->isoFormat('D MMM YYYY') }}
                                        </div>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-400">Setelah trial habis, berlangganan hanya <strong class="text-white">Rp 5.000/bulan</strong>.</p>
                                    <div class="p-3 bg-dark-card rounded-xl text-xs text-gray-400">
                                        Untuk berlangganan, ketik <code class="text-primary">/langganan</code> di bot Telegram kami.
                                    </div>
                                </div>
                            @else
                                <div class="space-y-3">
                                    <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-400">
                                        ❌ Akses berakhir. Berlangganan untuk melanjutkan.
                                    </div>
                                    <div class="p-3 bg-dark-card rounded-xl text-xs text-gray-400">
                                        Ketik <code class="text-primary">/langganan</code> di bot Telegram dan scan QRIS. Rp 5.000/bulan.
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Quick Commands -->
                        <div class="glass rounded-2xl p-5 border border-dark-border">
                            <h3 class="font-bold text-sm mb-4">⌨️ Perintah Bot</h3>
                            <div class="space-y-2 text-xs">
                                @foreach([
                                    ['/status', 'Cek status akun'],
                                    ['/langganan', 'Berlangganan / perpanjang'],
                                    ['/pause', 'Jedakan notifikasi'],
                                    ['/lanjut', 'Aktifkan notifikasi'],
                                    ['/hapus_akun', 'Hapus akun & data'],
                                ] as [$cmd, $desc])
                                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-dark-card transition-colors">
                                    <code class="text-primary font-mono">{{ $cmd }}</code>
                                    <span class="text-gray-500">{{ $desc }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
        <div class="glass rounded-xl px-5 py-4 shadow-lg border border-dark-border flex items-center gap-3">
            <span id="toastIcon">✅</span>
            <span id="toastMsg" class="text-sm font-medium"></span>
        </div>
    </div>

    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.getElementById('toast');
            document.getElementById('toastIcon').textContent = '❌';
            document.getElementById('toastMsg').textContent = '{{ session("error") }}';
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 5000);
        });
    </script>
    @endif
</body>
</html>
