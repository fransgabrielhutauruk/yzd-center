<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Services\KycService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $perjanjian = $user->dokumen->firstWhere('tipe_dokumen', 'perjanjian');

        return Inertia::render('Investor/Kyc/Index', [
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'nik'       => $user->nik,
                'phone'     => $user->phone,
                'alamat'    => $user->alamat,
                'role'      => $user->role,
                'status'    => $user->status,
                'is_active' => $user->is_active,
            ],
            'ahli_waris' => $user->ahliWaris,
            'dokumen'    => [
                'ktp' => $ktp ? [
                    'id'                 => $ktp->id,
                    'file_name'          => $ktp->file_name,
                    'file_size'          => $ktp->file_size,
                    'mime_type'          => $ktp->mime_type,
                    'status'             => $ktp->status,
                    'catatan_admin'      => $ktp->catatan_admin,
                    'is_quality_warning' => $ktp->is_quality_warning,
                    'verified_at'        => $ktp->verified_at?->toIso8601String(),
                    'preview_url'        => route('documents.show', $ktp->id, false),
                ] : null,
                'perjanjian' => $perjanjian ? [
                    'id'                 => $perjanjian->id,
                    'file_name'          => $perjanjian->file_name,
                    'file_size'          => $perjanjian->file_size,
                    'mime_type'          => $perjanjian->mime_type,
                    'status'             => $perjanjian->status,
                    'catatan_admin'      => $perjanjian->catatan_admin,
                    'is_quality_warning' => $perjanjian->is_quality_warning,
                    'verified_at'        => $perjanjian->verified_at?->toIso8601String(),
                    'preview_url'        => route('documents.show', $perjanjian->id, false),
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
}
