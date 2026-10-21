<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Proses ganti password pengguna yang sedang login.
     * Endpoint tunggal untuk semua role (admin, investor, affiliator).
     */
    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            $this->auditLogService->record(
                kategori: 'autentikasi',
                aksi: 'ganti_password',
                deskripsi: "Percobaan ganti password ditolak untuk {$user->name} (@{$user->username}): Kata sandi lama tidak cocok.",
                subject: $user,
                tingkat: 'warning',
                user: $user
            );

            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->auditLogService->record(
            kategori: 'autentikasi',
            aksi: 'ganti_password',
            deskripsi: "Pengguna {$user->name} (@{$user->username}) berhasil memperbarui kata sandi akun.",
            subject: $user,
            tingkat: 'warning',
            user: $user
        );

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
