<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
        if ($user && $user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user && $user->status !== 'active') {
            return redirect()->route('investor.account');
        }

        return redirect()->intended(route('investor.dashboard'));
    }

    /**
     * Logout pengguna.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}
