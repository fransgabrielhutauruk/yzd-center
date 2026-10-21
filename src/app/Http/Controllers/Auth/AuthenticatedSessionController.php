<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogService;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Tampilkan formulir login.
     */
    public function create(Request $request): Response
    {
        $lockoutSeconds = LoginRequest::getLockoutSeconds($request);

        return Inertia::render('Auth/Login', [
            'lockoutSeconds' => $lockoutSeconds,
        ]);
    }

    /**
     * Proses autentikasi login pengguna.
     *
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $login = $request->string('login')->trim()->value();
        $password = $request->string('password')->value();
        $remember = $request->boolean('remember');

        if (!$this->authService->authenticate($login, $password, $remember)) {
            $request->hitRateLimit();

            // Audit Log: Percobaan login gagal
            $this->auditLogService->record(
                kategori: 'autentikasi',
                aksi: 'login_gagal',
                deskripsi: "Percobaan login gagal untuk identitas: '{$login}'. Kata sandi atau username/email tidak cocok.",
                perubahan: ['meta' => ['login' => $login]],
                tingkat: 'warning'
            );

            if ($request->isRateLimited()) {
                event(new \Illuminate\Auth\Events\Lockout($request));
                $seconds = $request->availableIn();

                throw ValidationException::withMessages([
                    'login' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
                ]);
            }

            throw ValidationException::withMessages([
                'login' => 'Username/email atau kata sandi tidak cocok.',
            ]);
        }

        $request->clearRateLimit();
        $request->session()->regenerate();

        $user = $request->user();

        // Audit Log: Login berhasil
        if ($user) {
            $this->auditLogService->record(
                kategori: 'autentikasi',
                aksi: 'login',
                deskripsi: "Pengguna {$user->name} (@{$user->username}) berhasil masuk ke sistem.",
                subject: $user,
                tingkat: 'info',
                user: $user
            );
        }

        if ($user && $user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('investor.dashboard'));
    }

    /**
     * Logout pengguna.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Audit Log: Logout
        if ($user) {
            $this->auditLogService->record(
                kategori: 'autentikasi',
                aksi: 'logout',
                deskripsi: "Pengguna {$user->name} (@{$user->username}) telah keluar dari sistem.",
                subject: $user,
                tingkat: 'info',
                user: $user
            );
        }

        $this->authService->logout();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}
