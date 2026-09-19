<?php

namespace App\Services;

use App\Models\AkunCopytrade;
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

            // Simpan data ahli waris wajib
            UserAhliWaris::create([
                'user_id'           => $user->id,
                'nama'              => $data['ahli_waris_nama'],
                'hubungan_keluarga' => $data['ahli_waris_hubungan'],
                'no_hp'             => $data['ahli_waris_phone'],
                'alamat'            => $data['ahli_waris_alamat'],
            ]);

            // Tentukan detail akun berdasarkan tier
            $tierConfig = match ($data['tier_investasi']) {
                'tier_1' => ['broker' => 'HFM', 'tipe_akun' => 'cent', 'tanggal_selesai' => '2031-12-31'],
                'tier_2' => ['broker' => 'XM',  'tipe_akun' => 'standard', 'tanggal_selesai' => '2031-12-31'],
                'tier_3' => ['broker' => 'XM',  'tipe_akun' => 'private_vps', 'tanggal_selesai' => null],
            };

            $akunData = [
                'user_id'            => $user->id,
                'tier'               => $data['tier_investasi'],
                'broker'             => $tierConfig['broker'],
                'tipe_akun'          => $tierConfig['tipe_akun'],
                'nomor_akun_trading' => $data['nomor_akun_trading'],
                'estimasi_modal'     => $data['estimasi_modal'],
                'tanggal_mulai'      => $data['tanggal_mulai_trading'],
                'tanggal_selesai'    => $data['tier_investasi'] === 'tier_3'
                    ? date('Y-m-d', strtotime($data['tanggal_mulai_trading'] . ' +5 years'))
                    : $tierConfig['tanggal_selesai'],
                'nama_bank'          => $data['nama_bank'],
                'nomor_rekening'     => $data['nomor_rekening'],
                'atas_nama_rekening' => $data['atas_nama_rekening'],
                'status'             => 'pending',
            ];

            // Field khusus Tier 3
            if ($data['tier_investasi'] === 'tier_3') {
                $akunData['server_broker'] = $data['server_broker'] ?? null;
                $akunData['master_password'] = $data['master_password'] ?? null;
                $akunData['investor_password'] = $data['investor_password'] ?? null;
            }

            AkunCopytrade::create($akunData);

            // Unggah berkas dokumen KTP dan Surat Perjanjian
            if (isset($data['dokumen_ktp'])) {
                $this->kycService->uploadDocument($user, $data['dokumen_ktp'], 'ktp');
            }
            if (isset($data['dokumen_perjanjian'])) {
                $this->kycService->uploadDocument($user, $data['dokumen_perjanjian'], 'perjanjian');
            }

            $invitation->increment('total_terpakai');

            // Kirim notifikasi in-app ke seluruh administrator
            $admins = User::where('role', 'administrator')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewInvestorRegisteredNotification($user));
            }

            return $user;
        });
    }
}
