<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LaporanKeuntunganRequest;
use App\Models\LaporanKeuntungan;
use App\Models\LaporanKeuntunganGambar;
use App\Services\ProfitReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitReportController extends Controller
{
    public function __construct(
        private readonly ProfitReportService $profitReportService,
    ) {}

    /**
     * Tampilkan daftar laporan keuntungan dan materi promosi untuk admin.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['tipe', 'is_kalender', 'tahun', 'bulan', 'status', 'search', 'sort_by']);

        $reports = $this->profitReportService->getPaginatedReports(
            $filters,
            perPage: (int) $request->input('per_page', 10),
            onlyPublished: false
        );

        return Inertia::render('Admin/ProfitReports/Index', [
            'reports'    => $reports,
            'filters'    => $filters,
            'statistics' => $this->profitReportService->getStatistics(),
        ]);
    }

    /**
     * Simpan unggahan laporan keuntungan baru beserta foto-fotonya.
     */
    public function store(LaporanKeuntunganRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $images = $request->file('images', []);
        $captions = $request->input('captions', []);

        $this->profitReportService->createReport(
            $validated,
            is_array($images) ? $images : [],
            is_array($captions) ? $captions : [],
            $request->user()->id
        );

        return redirect()->back()->with('success', 'Laporan keuntungan dan foto materi promosi berhasil diterbitkan.');
    }

    /**
     * Perbarui laporan keuntungan yang sudah ada.
     */
    public function update(LaporanKeuntunganRequest $request, int $id): RedirectResponse
    {
        $report = LaporanKeuntungan::findOrFail($id);
        $validated = $request->validated();
        $newImages = $request->file('images', []);
        $deletedIds = $request->input('deleted_image_ids', []);
        $captions = $request->input('captions', []);

        $this->profitReportService->updateReport(
            $report,
            $validated,
            is_array($newImages) ? $newImages : [],
            is_array($deletedIds) ? $deletedIds : [],
            is_array($captions) ? $captions : []
        );

        return redirect()->back()->with('success', 'Laporan keuntungan berhasil diperbarui.');
    }

    /**
     * Hapus laporan keuntungan dan seluruh berkas fisik foto terkait.
     */
    public function destroy(int $id): RedirectResponse
    {
        $report = LaporanKeuntungan::findOrFail($id);
        $this->profitReportService->deleteReport($report);

        return redirect()->back()->with('success', 'Laporan keuntungan berhasil dihapus.');
    }

    /**
     * Stream foto laporan keuntungan (akses terotentikasi).
     */
    public function streamImage(Request $request, int $imageId): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Silakan login terlebih dahulu.');

        $image = LaporanKeuntunganGambar::with('laporanKeuntungan')->findOrFail($imageId);

        // Jika bukan admin, pastikan status laporan sudah published
        if (!$user->isAdmin() && $image->laporanKeuntungan->status !== 'published') {
            abort(403, 'Materi promosi ini belum dipublikasikan.');
        }

        if (!Storage::disk('local')->exists($image->file_path)) {
            abort(404, 'Berkas foto tidak ditemukan pada server.');
        }

        return Storage::disk('local')->response(
            $image->file_path,
            $image->file_name,
            [
                'Content-Type'           => $image->mime_type,
                'Cache-Control'          => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Unduh berkas foto laporan keuntungan untuk materi promosi.
     */
    public function downloadImage(Request $request, int $imageId): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Silakan login terlebih dahulu.');

        $image = LaporanKeuntunganGambar::with('laporanKeuntungan')->findOrFail($imageId);

        if (!$user->isAdmin() && $image->laporanKeuntungan->status !== 'published') {
            abort(403, 'Materi promosi ini belum dipublikasikan.');
        }

        if (!Storage::disk('local')->exists($image->file_path)) {
            abort(404, 'Berkas foto tidak ditemukan pada server.');
        }

        $safeDownloadName = 'YZD-Profit-' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $image->file_name);

        return Storage::disk('local')->download($image->file_path, $safeDownloadName);
    }
}
