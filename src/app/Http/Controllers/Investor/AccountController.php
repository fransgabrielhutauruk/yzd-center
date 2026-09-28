<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Services\KycService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(
        private readonly KycService $kycService,
    ) {}

    /**
     * Tampilkan halaman informasi akun & status verifikasi identitas investor.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load(['ahliWaris', 'dokumen', 'akunCopytrade']);

        $ktp = $user->dokumen->firstWhere('tipe_dokumen', 'ktp');
        $perjanjianDokumen = $user->dokumen->firstWhere('tipe_dokumen', 'perjanjian');

        $perjanjian = null;
        if ($perjanjianDokumen) {
            $perjanjian = [
                'id'                 => $perjanjianDokumen->id,
                'file_name'          => $perjanjianDokumen->file_name,
                'file_size'          => $perjanjianDokumen->file_size,
                'mime_type'          => $perjanjianDokumen->mime_type,
                'status'             => $perjanjianDokumen->status,
                'catatan_admin'      => $perjanjianDokumen->status === 'approved' ? null : $perjanjianDokumen->catatan_admin,
                'is_quality_warning' => $perjanjianDokumen->is_quality_warning,
                'verified_at'        => $perjanjianDokumen->verified_at?->toIso8601String(),
                'preview_url'        => route('documents.show', $perjanjianDokumen->id, false) . '?v=' . ($perjanjianDokumen->updated_at?->timestamp ?? time()),
            ];
        } else {
            // Cek berkas surat perjanjian dari pendaftaran copytrade jika belum ada di user_dokumen
            $akunWithPerjanjian = $user->akunCopytrade
                ->whereNotNull('surat_perjanjian_path')
                ->filter(fn ($a) => Storage::disk('local')->exists($a->surat_perjanjian_path))
                ->last();

            if ($akunWithPerjanjian) {
                $filePath = $akunWithPerjanjian->surat_perjanjian_path;
                $fileSize = Storage::disk('local')->size($filePath);
                $mimeType = Storage::disk('local')->mimeType($filePath) ?? 'application/pdf';

                $perjanjian = [
                    'id'                 => $akunWithPerjanjian->id,
                    'file_name'          => 'Surat_Perjanjian_Kemitraan.pdf',
                    'file_size'          => $fileSize,
                    'mime_type'          => $mimeType,
                    'status'             => $akunWithPerjanjian->status === 'active' ? 'approved' : ($akunWithPerjanjian->status === 'rejected' ? 'revision_needed' : 'pending'),
                    'catatan_admin'      => $akunWithPerjanjian->status === 'active' ? null : $akunWithPerjanjian->catatan_admin,
                    'is_quality_warning' => false,
                    'verified_at'        => $akunWithPerjanjian->verified_at?->toIso8601String(),
                    'preview_url'        => route('documents.copytrade', $akunWithPerjanjian->id, false) . '?v=' . ($akunWithPerjanjian->updated_at?->timestamp ?? time()),
                ];
            }
        }

        return Inertia::render('Investor/Account/Index', [
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'username'      => $user->username,
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
                    'preview_url'        => route('documents.show', $ktp->id, false) . '?v=' . ($ktp->updated_at?->timestamp ?? time()),
                ] : null,
                'perjanjian' => $perjanjian,
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
}
