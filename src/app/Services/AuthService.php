<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\KontenLegal;
use App\Models\User;
use App\Models\UserAhliWaris;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly KycService $kycService,
    ) {}

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

            $terms = KontenLegal::getTerms();
            $termsVersion = $terms?->versi ?? 'v1.0';

            $user = User::create([
                'name'            => $data['name'],
                'username'        => strtolower($data['username']),
                'email'           => strtolower($data['email']),
                'password'        => Hash::make($data['password']),
                'phone'           => $data['phone'],
                'nik'             => $data['nik'],
                'alamat'          => $data['alamat'],
                'role'            => $invitation->target_role,
                'status'          => 'pending',
                'referral_code'   => $referralCode,
                'is_active'       => true,
                'terms_agreed_at' => now(),
                'terms_version'   => $termsVersion,
                'ip_address'      => $ip,
                'created_by'      => 'invite:' . substr($invitation->token, 0, 8),
            ]);

            // Simpan data ahli waris wajib
            UserAhliWaris::create([
                'user_id'           => $user->id,
                'nama'              => $data['ahli_waris_nama'],
                'hubungan_keluarga' => $data['ahli_waris_hubungan'],
                'no_hp'             => $data['ahli_waris_phone'],
                'alamat'            => $data['ahli_waris_alamat'],
            ]);

            // Unggah berkas dokumen KTP dan Surat Perjanjian
            if (isset($data['dokumen_ktp'])) {
                $this->kycService->uploadDocument($user, $data['dokumen_ktp'], 'ktp');
            }
            if (isset($data['dokumen_perjanjian'])) {
                $this->kycService->uploadDocument($user, $data['dokumen_perjanjian'], 'perjanjian');
            }

            $invitation->increment('total_terpakai');

            Auth::login($user);

            return $user;
        });
    }
}
