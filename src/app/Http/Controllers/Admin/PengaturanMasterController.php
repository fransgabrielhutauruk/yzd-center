<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanMaster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengaturanMasterController extends Controller
{
    /**
     * Tampilkan formulir konfigurasi master copytrade dan tier.
     */
    public function index(): Response
    {
        $pengaturan = PengaturanMaster::getSettings();

        return Inertia::render('Admin/Settings/Master', [
            'pengaturan' => [
                'id'                   => $pengaturan->id,
                'link_referral_broker' => $pengaturan->link_referral_broker ?? '',
                'link_master_akun'     => $pengaturan->link_master_akun ?? '',
                'tier_1_min'           => (float) $pengaturan->tier_1_min,
                'tier_2_min'           => (float) $pengaturan->tier_2_min,
                'tier_3_min'           => (float) $pengaturan->tier_3_min,
                'tier_1_desc'          => $pengaturan->tier_1_desc ?? '',
                'tier_2_desc'          => $pengaturan->tier_2_desc ?? '',
                'tier_3_desc'          => $pengaturan->tier_3_desc ?? '',
                'tier_1_pdf_url'       => $pengaturan->tier_1_pdf_path ? route('admin.settings.master.download_pdf', 'tier_1') : null,
                'tier_2_pdf_url'       => $pengaturan->tier_2_pdf_path ? route('admin.settings.master.download_pdf', 'tier_2') : null,
                'tier_3_pdf_url'       => $pengaturan->tier_3_pdf_path ? route('admin.settings.master.download_pdf', 'tier_3') : null,
                'tier_1_pdf_name'      => $pengaturan->tier_1_pdf_path ? basename($pengaturan->tier_1_pdf_path) : null,
                'tier_2_pdf_name'      => $pengaturan->tier_2_pdf_path ? basename($pengaturan->tier_2_pdf_path) : null,
                'tier_3_pdf_name'      => $pengaturan->tier_3_pdf_path ? basename($pengaturan->tier_3_pdf_path) : null,
            ],
        ]);
    }

    /**
     * Simpan perubahan pengaturan master copytrade.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'link_referral_broker' => ['nullable', 'url', 'max:255'],
            'link_master_akun'     => ['nullable', 'url', 'max:255'],
            'tier_1_min'           => ['required', 'numeric', 'min:0'],
            'tier_2_min'           => ['required', 'numeric', 'min:0'],
            'tier_3_min'           => ['required', 'numeric', 'min:0'],
            'tier_1_desc'          => ['nullable', 'string', 'max:5000'],
            'tier_2_desc'          => ['nullable', 'string', 'max:5000'],
            'tier_3_desc'          => ['nullable', 'string', 'max:5000'],
            'tier_1_pdf'           => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'tier_2_pdf'           => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'tier_3_pdf'           => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove_tier_1_pdf'    => ['nullable', 'boolean'],
            'remove_tier_2_pdf'    => ['nullable', 'boolean'],
            'remove_tier_3_pdf'    => ['nullable', 'boolean'],
        ], [
            'link_referral_broker.url' => 'Format tautan referral broker harus berupa URL yang valid (contoh: https://...).',
            'link_master_akun.url'     => 'Format tautan master akun harus berupa URL yang valid (contoh: https://...).',
            'tier_1_min.required'      => 'Batas minimum modal Tier 1 wajib ditentukan.',
            'tier_1_min.numeric'       => 'Batas modal Tier 1 harus berupa angka.',
            'tier_2_min.required'      => 'Batas minimum modal Tier 2 wajib ditentukan.',
            'tier_2_min.numeric'       => 'Batas modal Tier 2 harus berupa angka.',
            'tier_3_min.required'      => 'Batas minimum modal Tier 3 wajib ditentukan.',
            'tier_3_min.numeric'       => 'Batas modal Tier 3 harus berupa angka.',
            'tier_1_pdf.mimes'         => 'Berkas panduan Tier 1 harus berformat PDF.',
            'tier_1_pdf.max'           => 'Ukuran berkas panduan Tier 1 maksimal 10 MB.',
            'tier_2_pdf.mimes'         => 'Berkas panduan Tier 2 harus berformat PDF.',
            'tier_2_pdf.max'           => 'Ukuran berkas panduan Tier 2 maksimal 10 MB.',
            'tier_3_pdf.mimes'         => 'Berkas panduan Tier 3 harus berformat PDF.',
            'tier_3_pdf.max'           => 'Ukuran berkas panduan Tier 3 maksimal 10 MB.',
        ]);

        $pengaturan = PengaturanMaster::getSettings();

        $updateData = [
            'link_referral_broker' => $validated['link_referral_broker'] ?? null,
            'link_master_akun'     => $validated['link_master_akun'] ?? null,
            'tier_1_min'           => $validated['tier_1_min'],
            'tier_2_min'           => $validated['tier_2_min'],
            'tier_3_min'           => $validated['tier_3_min'],
            'tier_1_desc'          => $validated['tier_1_desc'] ?? null,
            'tier_2_desc'          => $validated['tier_2_desc'] ?? null,
            'tier_3_desc'          => $validated['tier_3_desc'] ?? null,
        ];

        // Handle Tier PDFs
        foreach (['tier_1', 'tier_2', 'tier_3'] as $tierKey) {
            $pdfKey = "{$tierKey}_pdf";
            $removeKey = "remove_{$tierKey}_pdf";
            $pathColumn = "{$tierKey}_pdf_path";

            if ($request->boolean($removeKey)) {
                if ($pengaturan->$pathColumn && Storage::disk('local')->exists($pengaturan->$pathColumn)) {
                    Storage::disk('local')->delete($pengaturan->$pathColumn);
                }
                $updateData[$pathColumn] = null;
            } elseif ($request->hasFile($pdfKey)) {
                if ($pengaturan->$pathColumn && Storage::disk('local')->exists($pengaturan->$pathColumn)) {
                    Storage::disk('local')->delete($pengaturan->$pathColumn);
                }
                $updateData[$pathColumn] = $request->file($pdfKey)->store('master_guides', 'local');
            }
        }

        $pengaturan->update($updateData);

        return redirect()->back()->with('success', 'Konfigurasi Master, Tier, dan Panduan PDF Copytrade berhasil disimpan.');
    }

    /**
     * Unduh atau pratinjau berkas PDF panduan tier copytrade.
     */
    public function downloadPdf(string $tier): StreamedResponse
    {
        if (!in_array($tier, ['tier_1', 'tier_2', 'tier_3'], true)) {
            abort(404, 'Tier tidak ditemukan.');
        }

        $pengaturan = PengaturanMaster::getSettings();
        $pathColumn = "{$tier}_pdf_path";
        $path = $pengaturan->$pathColumn;

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404, 'Berkas panduan PDF untuk tier ini belum diunggah.');
        }

        $tierLabels = [
            'tier_1' => 'Tier-1-Cent',
            'tier_2' => 'Tier-2-Standard',
            'tier_3' => 'Tier-3-Private-VPS',
        ];
        $filename = "Panduan-Copytrade-{$tierLabels[$tier]}.pdf";

        return Storage::disk('local')->response(
            $path,
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
