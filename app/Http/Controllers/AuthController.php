<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MoodleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private MoodleService $moodleService) {}

    // ─── Show Landing Page ────────────────────────────────────────────────────

    public function index()
    {
        $user = session('user_id') ? User::find(session('user_id')) : null;
        return view('landing', compact('user'));
    }

    // ─── Show Login Page ──────────────────────────────────────────────────────────

    public function showLogin()
    {
        // Already logged in → go to dashboard
        if (session('user_id') && User::find(session('user_id'))) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    // ─── Handle Login / Registration ──────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email', 'ends_with:upnjatim.ac.id'],
            'password' => ['required', 'min:6'],
        ], [
            'email.ends_with' => 'Gunakan email UPN Jatim kamu (@upnjatim.ac.id).',
        ]);

        try {
            // 1. Fetch Moodle token (password is NOT saved)
            $token = $this->moodleService->fetchToken(
                $request->input('email'),
                $request->input('password')
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
                ]
            );

            // 4. Update token and generate sync code
            $syncCode = 'SYNC-' . strtoupper(Str::random(8));
            $user->update([
                'moodle_token' => $token,
                'sync_code'    => $syncCode,
                'last_moodle_error' => null,
            ]);

            // 5. Store session
            session(['user_id' => $user->id]);

            return response()->json([
                'success'   => true,
                'user_name' => $user->name,
                'sync_code' => $syncCode,
                'bot_username' => config('services.telegram.bot_username', 'Ilmu2Reminder_Bot'),
                'telegram_linked' => $user->hasTelegramLinked(),
                'has_access' => $user->hasActiveAccess(),
            ]);

        } catch (\Exception $e) {
            Log::warning('Login failed', ['email' => $request->input('email'), 'error' => $e->getMessage()]);

            $message = match (true) {
                str_contains($e->getMessage(), 'invalid') || str_contains($e->getMessage(), 'Invalid') =>
                    'Email atau password salah. Pastikan kamu menggunakan kredensial Ilmu 2.',
                str_contains($e->getMessage(), 'terhubung') =>
                    'Tidak bisa terhubung ke server Ilmu 2. Coba lagi dalam beberapa menit.',
                default => 'Terjadi kesalahan. Silakan coba lagi.',
            };

            return response()->json(['success' => false, 'message' => $message], 422);
        }
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $userId = session('user_id');
        if (!$userId) {
            return redirect('/')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user  = User::with(['taskReminders' => function ($q) {
            $q->where('deadline', '>=', now())->orderBy('deadline');
        }, 'paymentVerifications' => function ($q) {
            $q->latest()->limit(5);
        }])->findOrFail($userId);

        $syncCode    = 'SYNC-' . strtoupper(\Illuminate\Support\Str::random(8));
        $user->update(['sync_code' => $syncCode]);

        return view('dashboard', compact('user', 'syncCode'));
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout()
    {
        session()->forget('user_id');
        return redirect('/');
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function extractNameFromEmail(string $email): string
    {
        $local = explode('@', $email)[0];
        // Format: firstname.lastname or npm
        $parts = explode('.', $local);
        return implode(' ', array_map('ucfirst', $parts));
    }
}
