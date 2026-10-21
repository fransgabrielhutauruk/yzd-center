<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\KontenLegal;
use App\Models\User;
use App\Models\UserAhliWaris;
use App\Notifications\NewInvestorRegisteredNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly KycService $kycService,
        private readonly ReferralService $referralService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Autentikasi pengguna menggunakan email atau username.
     */
    public function authenticate(string $login, string $password, bool $remember = false): bool
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $login)->first();
        if ($user && !$user->is_active && Hash::check($password, $user->password)) {
            $this->auditLogService->record(
                kategori: 'autentikasi',
                aksi: 'login_gagal',
                deskripsi: "Percobaan login ditolak: Akun {$user->name} (@{$user->username}) berstatus nonaktif.",
                subject: $user,
                perubahan: ['meta' => ['username' => $login, 'alasan' => 'Akun dinonaktifkan']],
                tingkat: 'warning',
                user: $user
            );

            throw ValidationException::withMessages([
                'login' => 'Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi admin.',
            ]);
        }

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
                $referralCode = $this->referralService->generateReferralCode($data['name']);
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
                'tempat_lahir'    => $data['tempat_lahir'],
                'tanggal_lahir'   => $data['tanggal_lahir'],
                'pekerjaan'       => $data['pekerjaan'],
                'role'            => $invitation->target_role,
                'status'          => 'pending',
                'referral_code'   => $referralCode,
                'is_active'       => true,
                'terms_agreed_at' => now(),
                'terms_version'   => $termsVersion,
                'ip_address'      => $ip,
                'created_by'      => 'invite:' . substr($invitation->token, 0, 8),
            ]);

            // Rekam relasi referral jika pendaftar menginput kode referral affiliator
            if (!empty($data['referral_code'])) {
                $affiliator = $this->referralService->validateReferralCode($data['referral_code']);
                if ($affiliator && $affiliator->id !== $user->id) {
                    $this->referralService->attachDownline($affiliator, $user, 'link_otomatis');
                }
            }

            // Simpan data ahli waris wajib
            UserAhliWaris::create([
                'user_id'           => $user->id,
                'nama'              => $data['ahli_waris_nama'],
                'hubungan_keluarga' => $data['ahli_waris_hubungan'],
                'no_hp'             => $data['ahli_waris_phone'],
                'alamat'            => $data['ahli_waris_alamat'],
            ]);

            // Unggah berkas dokumen KTP
            if (isset($data['dokumen_ktp'])) {
                $this->kycService->uploadDocument($user, $data['dokumen_ktp'], 'ktp');
            }

            $invitation->increment('total_terpakai');

            // Audit Log: Pendaftaran akun baru
            $this->auditLogService->record(
                kategori: 'autentikasi',
                aksi: 'register',
                deskripsi: "Pendaftaran akun baru {$user->name} (@{$user->username}) sebagai {$user->role} via undangan.",
                subject: $user,
                perubahan: [
                    'sesudah' => [
                        'name'     => $user->name,
                        'username' => $user->username,
                        'email'    => $user->email,
                        'role'     => $user->role,
                        'status'   => $user->status,
                        'nik'      => substr((string) $user->nik, 0, 6) . '******' . substr((string) $user->nik, -4),
                    ],
                    'meta' => [
                        'invitation_token' => substr($invitation->token, 0, 8) . '...',
                        'referral_code'    => $data['referral_code'] ?? null,
                    ],
                ],
                tingkat: 'info',
                user: $user
            );

            // Kirim notifikasi in-app ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewInvestorRegisteredNotification($user));
            }

            return $user;
        });
    }
}
