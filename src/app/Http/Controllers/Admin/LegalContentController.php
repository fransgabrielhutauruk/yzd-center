<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KontenLegal;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalContentController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * Tampilkan halaman pengelolaan Syarat & Ketentuan serta Kebijakan Privasi.
     */
    public function index(): Response
    {
        $terms = KontenLegal::where('key_name', 'terms_conditions')->first();
        $privacy = KontenLegal::where('key_name', 'privacy_policy')->first();

        return Inertia::render('Admin/Settings/Legal', [
            'terms'   => $terms,
            'privacy' => $privacy,
        ]);
    }

    /**
     * Perbarui konten dokumen legalitas.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key_name' => ['required', 'string', 'in:terms_conditions,privacy_policy'],
            'judul'    => ['required', 'string', 'max:200'],
            'konten'   => ['required', 'string'],
        ], [
            'judul.required'  => 'Judul dokumen wajib diisi.',
            'konten.required' => 'Isi pasal dokumen wajib diisi.',
        ]);

        $existing = KontenLegal::where('key_name', $validated['key_name'])->first();
        $dataSebelum = $existing ? [
            'judul'          => $existing->judul,
            'konten_preview' => mb_substr(strip_tags($existing->konten), 0, 100),
        ] : null;

        $kontenLegal = KontenLegal::updateOrCreate(
            ['key_name' => $validated['key_name']],
            [
                'judul'      => $validated['judul'],
                'konten'     => $validated['konten'],
                'updated_by' => $request->user()->id,
            ]
        );

        KontenLegal::clearCache();

        $label = $validated['key_name'] === 'terms_conditions' ? 'Syarat & Ketentuan' : 'Kebijakan Privasi';

        $this->auditLogService->record(
            kategori: 'edukasi_pengaturan',
            aksi: 'ubah_konten_legal',
            deskripsi: "Memperbarui dokumen legalitas {$label}",
            subject: $kontenLegal,
            perubahan: [
                'sebelum' => $dataSebelum,
                'sesudah' => [
                    'judul'          => $validated['judul'],
                    'konten_preview' => mb_substr(strip_tags($validated['konten']), 0, 100),
                ],
            ],
            tingkat: 'warning',
            user: $request->user(),
        );

        return redirect()->back()
            ->with('success', "Dokumen {$label} berhasil diperbarui.");
    }
}
