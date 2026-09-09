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
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
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

            throw ValidationException::withMessages([
                'login' => 'Kombinasi kredensial login atau kata sandi tidak cocok, atau akun Anda sedang dinonaktifkan.',
            ]);
        }

        $request->clearRateLimit();
        $request->session()->regenerate();

        $user = $request->user();
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
        $this->authService->logout();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari akun.');
    }
}
