<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly InvitationService $invitationService,
    ) {}

    /**
     * Tampilkan formulir pendaftaran tertutup via token undangan.
     */
    public function create(Request $request): Response
    {
        $token = $request->query('invite');
        $invitationStatus = $this->invitationService->validateToken($token);

        return Inertia::render('Auth/Register', [
            'invitation' => $invitationStatus,
            'token'      => $token,
        ]);
    }

    /**
     * Proses pendaftaran pengguna baru.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $token = $request->string('invite_token')->value();
        $ip = $request->ip() ?? '127.0.0.1';

        $this->authService->registerWithInvitation($validated, $token, $ip);

        return redirect()->route('investor.dashboard')
            ->with('success', 'Pendaftaran akun berhasil! Selamat datang di YZD Trading.');
    }
}
