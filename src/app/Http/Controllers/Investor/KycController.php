<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Services\KycService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KycController extends Controller
{
    public function __construct(
        private readonly KycService $kycService,
    ) {}

    /**
     * Tampilkan halaman status verifikasi KYC & legalitas investor.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load(['ahliWaris', 'dokumen']);

        $ktp = $user->dokumen->firstWhere('tipe_dokumen', 'ktp');

        return Inertia::render('Investor/Kyc/Index', [
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'nik'           => $user->nik,
                'phone'         => $user->phone,
                'alamat'        => $user->alamat,
                'tempat_lahir'  => $user->tempat_lahir,
                'tanggal_lahir' => $user->tanggal_lahir ? tanggal_indo($user->tanggal_lahir) : null,
                'pekerjaan'     => $user->pekerjaan,
                'role'          => $user->role,
                'status'        => $user->status,
                'is_active'     => $user->is_active,
            ],
            'ahli_waris' => $user->ahliWaris ? [
                'nama'              => $user->ahliWaris->nama,
                'hubungan_keluarga' => $user->ahliWaris->hubungan_keluarga,
                'no_hp'             => $user->ahliWaris->no_hp,
                'alamat'            => $user->ahliWaris->alamat,
            ] : null,

            'dokumen' => [
                'ktp' => $ktp ? [
                    'id'                 => $ktp->id,
                    'file_name'          => $ktp->file_name,
                    'file_size'          => $ktp->file_size,
                    'mime_type'          => $ktp->mime_type,
                    'status'             => $ktp->status,
                    'catatan_admin'      => $ktp->status === 'approved' ? null : $ktp->catatan_admin,
                    'is_quality_warning' => $ktp->is_quality_warning,
                    'verified_at'        => $ktp->verified_at?->toIso8601String(),
                    'preview_url'        => route('documents.show', $ktp->id, false),
                ] : null,

            ],
            'is_kyc_approved' => $user->isKycApproved(),
        ]);
    }

    /**
     * Unggah ulang berkas dokumen yang berstatus revision_needed.
     */
    public function reupload(Request $request, string $type): RedirectResponse
    {
        if (!in_array($type, ['ktp', 'perjanjian'], true)) {
            abort(404);
        }

        $rules = $type === 'ktp'
            ? ['file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240']]
            : ['file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240']];

        $request->validate($rules, [
            'file.required' => 'Pilih berkas dokumen yang ingin diunggah ulang.',
            'file.mimes'    => $type === 'ktp' ? 'Foto KTP harus berupa format JPG, JPEG, atau PNG.' : 'Surat perjanjian harus berupa format JPG, PNG, atau PDF.',
            'file.max'      => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $this->kycService->reuploadDocument($request->user(), $request->file('file'), $type);

        $label = $type === 'ktp' ? 'Foto KTP' : 'Surat Perjanjian';

        return redirect()->back()
            ->with('success', "Berkas {$label} berhasil diunggah ulang dan sedang menunggu peninjauan kembali oleh Administrator.");
    }

    /**
     * Perbarui data akun copy trade & rekening oleh investor.
     */
    public function updateAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $akun = $user->akunCopytrade;

        if (!$akun) {
            abort(404, 'Data akun copy trade tidak ditemukan.');
        }

        $tier = $akun->tier;

        $rules = [
            'nomor_akun_trading' => ['required', 'string', 'max:50'],
            'estimasi_modal'     => ['required', 'numeric', 'min:100'],
            'nama_bank'          => ['required', 'string', 'max:100'],
            'nomor_rekening'     => ['required', 'string', 'max:50'],
            'atas_nama_rekening' => ['required', 'string', 'max:100'],
            'server_broker'      => ['nullable', 'string', 'max:100'],
            'master_password'    => ['nullable', 'string', 'max:255'],
            'investor_password'  => ['nullable', 'string', 'max:255'],
        ];

        if ($tier === 'tier_3') {
            $rules['server_broker'] = ['required', 'string', 'max:100'];
        }

        $messages = [
            'nomor_akun_trading.required' => 'Nomor akun trading MT4 wajib diisi.',
            'estimasi_modal.required'     => 'Estimasi modal trading wajib diisi.',
            'estimasi_modal.numeric'      => 'Estimasi modal harus berupa angka nominal.',
            'estimasi_modal.min'          => 'Estimasi modal minimal $100.',
            'nama_bank.required'          => 'Pilih atau isi nama bank pencairan.',
            'nomor_rekening.required'     => 'Nomor rekening pencairan wajib diisi.',
            'atas_nama_rekening.required' => 'Nama pemilik rekening wajib diisi.',
            'server_broker.required'      => 'Server broker XM wajib diisi untuk Tier 3 VPS.',
        ];

        $validated = $request->validate($rules, $messages);

        $modal = (float) $validated['estimasi_modal'];
        if ($tier === 'tier_1' && $modal >= 5000) {
            throw ValidationException::withMessages([
                'estimasi_modal' => 'Tier 1 hanya untuk modal di bawah $5,000.',
            ]);
        } elseif ($tier === 'tier_2' && ($modal < 5000 || $modal > 300000)) {
            throw ValidationException::withMessages([
                'estimasi_modal' => 'Tier 2 hanya untuk modal $5,000 – $300,000.',
            ]);
        } elseif ($tier === 'tier_3' && $modal < 300000) {
            throw ValidationException::withMessages([
                'estimasi_modal' => 'Tier 3 membutuhkan modal minimal $300,000.',
            ]);
        }

        $this->kycService->updateCopytradeAccount($user, $validated);

        return redirect()->back()
            ->with('success', 'Data akun copy trade & rekening pencairan berhasil diperbarui. Status akun siap diverifikasi kembali oleh Administrator.');
    }

    /**
     * Konfirmasi follow master copy trade oleh investor untuk memicu peninjauan ulang admin.
     */
    public function confirmFollow(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->kycService->confirmFollowMaster($user);

        return redirect()->back()
            ->with('success', 'Konfirmasi follow master berhasil dikirim. Tim Admin akan memeriksa dan memvalidasi sambungan akun trading Anda.');
    }
}
