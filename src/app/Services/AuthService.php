<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Autentikasi pengguna menggunakan email atau username.
     */
    public function authenticate(string $login, string $password, bool $remember = false): bool
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return Auth::attempt([
            $field      => $login,
            'password'  => $password,
            'is_active' => true,
        ], $remember);
    }

    /**
     * Logout pengguna dan invalidasi sesi.
     */
    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Proses pendaftaran pengguna baru menggunakan token undangan.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function registerWithInvitation(array $data, string $token, string $ip): User
    {
        return DB::transaction(function () use ($data, $token, $ip): User {
            /** @var Invitation|null $invitation */
            $invitation = Invitation::query()
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if (!$invitation || !$invitation->isValid()) {
                throw ValidationException::withMessages([
                    'invite_token' => 'Tautan pendaftaran ini sudah tidak valid, telah kedaluwarsa, atau kuotanya telah habis.',
                ]);
            }

            $referralCode = null;
            if ($invitation->target_role === 'affiliator') {
                $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['username']));
                $referralCode = $baseCode . rand(100, 999);
                while (User::where('referral_code', $referralCode)->exists()) {
                    $referralCode = $baseCode . rand(1000, 9999);
                }
            }

            $user = User::create([
                'name'            => $data['name'],
                'username'        => strtolower($data['username']),
                'email'           => strtolower($data['email']),
                'password'        => Hash::make($data['password']),
                'phone'           => $data['phone'],
                'nik'             => $data['nik'],
                'alamat'          => $data['alamat'],
                'role'            => $invitation->target_role,
                'referral_code'   => $referralCode,
                'is_active'       => true,
                'terms_agreed_at' => now(),
                'terms_version'   => 'v1.0',
                'ip_address'      => $ip,
                'created_by'      => 'invite:' . substr($invitation->token, 0, 8),
            ]);

            $invitation->increment('total_terpakai');

            Auth::login($user);

            return $user;
        });
    }
}
