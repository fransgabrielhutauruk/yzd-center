<?php

namespace App\Services;

use App\Models\LaporanKeuntungan;
use App\Models\LaporanKeuntunganGambar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfitReportService
{
    /**
     * Dapatkan daftar laporan keuntungan dengan pagination dan filter dinamis.
     */
    public function getPaginatedReports(array $filters = [], int $perPage = 10, bool $onlyPublished = false): LengthAwarePaginator
    {
        $query = LaporanKeuntungan::with(['gambar', 'creator']);

        if ($onlyPublished) {
            $query->where('status', 'published');
        } elseif (!empty($filters['status']) && in_array($filters['status'], ['published', 'draft'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipe']) && in_array($filters['tipe'], ['harian', 'bulanan'])) {
            $query->where('tipe', $filters['tipe']);
        }

        if (isset($filters['is_kalender']) && $filters['is_kalender'] !== '' && $filters['is_kalender'] !== null) {
            $query->where('is_kalender', filter_var($filters['is_kalender'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['tahun'])) {
            $tahun = (int) $filters['tahun'];
            $query->where(function ($q) use ($tahun) {
                $q->where('tahun', $tahun)
                    ->orWhereYear('tanggal', $tahun);
            });
        }

        if (!empty($filters['bulan'])) {
            $bulan = (int) $filters['bulan'];
            $query->where(function ($q) use ($bulan) {
                $q->where('bulan', $bulan)
                    ->orWhereMonth('tanggal', $bulan);
            });
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Pengurutan kronologis terbaru
        $sortBy = $filters['sort_by'] ?? 'latest';
        if ($sortBy === 'oldest') {
            $query->orderBy('tanggal')->orderBy('tahun')->orderBy('bulan')->orderBy('id');
        } else {
            $query->orderByDesc('tanggal')->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Simpan laporan keuntungan baru beserta multi-gambar.
     *
     * @param array<string, mixed> $data
     * @param array<UploadedFile> $images
     * @param array<string> $captions
     */
    public function createReport(array $data, array $images, array $captions, int $userId): LaporanKeuntungan
    {
        return DB::transaction(function () use ($data, $images, $captions, $userId) {
            $report = LaporanKeuntungan::create([
                'tipe'               => $data['tipe'],
                'judul'              => $data['judul'],
                'tanggal'            => $data['tipe'] === 'harian' ? ($data['tanggal'] ?? null) : null,
                'bulan'              => $data['tipe'] === 'bulanan' ? ($data['bulan'] ?? null) : null,
                'tahun'              => $data['tipe'] === 'bulanan' ? ($data['tahun'] ?? null) : null,
                'profit_persen'      => $data['profit_persen'] ?? null,
                'profit_nominal_usd' => $data['profit_nominal_usd'] ?? null,
                'is_kalender'        => filter_var($data['is_kalender'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'deskripsi'          => $data['deskripsi'] ?? null,
                'status'             => $data['status'] ?? 'published',
                'created_by'         => $userId,
            ]);

            $this->storeImages($report, $images, $captions);

            return $report->fresh(['gambar', 'creator']);
        });
    }

    /**
     * Perbarui laporan keuntungan, hapus gambar yang dipilih, dan unggah gambar baru jika ada.
     *
     * @param array<string, mixed> $data
     * @param array<UploadedFile> $newImages
     * @param array<int> $deletedImageIds
     * @param array<string> $captions
     */
    public function updateReport(
        LaporanKeuntungan $report,
        array $data,
        array $newImages = [],
        array $deletedImageIds = [],
        array $captions = []
    ): LaporanKeuntungan {
        return DB::transaction(function () use ($report, $data, $newImages, $deletedImageIds, $captions) {
            $report->update([
                'tipe'               => $data['tipe'],
                'judul'              => $data['judul'],
                'tanggal'            => $data['tipe'] === 'harian' ? ($data['tanggal'] ?? null) : null,
                'bulan'              => $data['tipe'] === 'bulanan' ? ($data['bulan'] ?? null) : null,
                'tahun'              => $data['tipe'] === 'bulanan' ? ($data['tahun'] ?? null) : null,
                'profit_persen'      => $data['profit_persen'] ?? null,
                'profit_nominal_usd' => $data['profit_nominal_usd'] ?? null,
                'is_kalender'        => filter_var($data['is_kalender'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'deskripsi'          => $data['deskripsi'] ?? null,
                'status'             => $data['status'] ?? 'published',
            ]);

            // Hapus gambar yang ditandai
            if (!empty($deletedImageIds)) {
                $imagesToDelete = LaporanKeuntunganGambar::where('laporan_keuntungan_id', $report->id)
                    ->whereIn('id', $deletedImageIds)
                    ->get();

                foreach ($imagesToDelete as $img) {
                    if (Storage::disk('local')->exists($img->file_path)) {
                        Storage::disk('local')->delete($img->file_path);
                    }
                    $img->delete();
                }
            }

            // Simpan gambar-gambar baru
            if (!empty($newImages)) {
                $lastOrder = (int) $report->gambar()->max('urutan');
                $this->storeImages($report, $newImages, $captions, $lastOrder + 1);
            }

            return $report->fresh(['gambar', 'creator']);
        });
    }

    /**
     * Hapus laporan keuntungan dan semua berkas fisiknya.
     */
    public function deleteReport(LaporanKeuntungan $report): void
    {
        DB::transaction(function () use ($report) {
            // Hapus berkas fisik semua gambar
            foreach ($report->gambar as $img) {
                if (Storage::disk('local')->exists($img->file_path)) {
                    Storage::disk('local')->delete($img->file_path);
                }
            }

            // Hapus folder direktori jika kosong
            $folderPath = "profit_reports/{$report->id}";
            if (Storage::disk('local')->exists($folderPath)) {
                Storage::disk('local')->deleteDirectory($folderPath);
            }

            $report->delete();
        });
    }

    /**
     * Dapatkan ringkasan statistik laporan keuntungan untuk dashboard admin & affiliator.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $totalPublished = LaporanKeuntungan::where('status', 'published')->count();
        $totalHarian = LaporanKeuntungan::where('status', 'published')->where('tipe', 'harian')->count();
        $totalBulanan = LaporanKeuntungan::where('status', 'published')->where('tipe', 'bulanan')->count();
        $totalKalender = LaporanKeuntungan::where('status', 'published')->where('is_kalender', true)->count();
        $totalFoto = LaporanKeuntunganGambar::whereHas('laporanKeuntungan', fn ($q) => $q->where('status', 'published'))->count();

        // Rata-rata profit bulanan tahun ini
        $avgProfitBulanan = LaporanKeuntungan::where('status', 'published')
            ->where('tipe', 'bulanan')
            ->where('tahun', date('Y'))
            ->whereNotNull('profit_persen')
            ->avg('profit_persen');

        return [
            'total_published'    => $totalPublished,
            'total_harian'       => $totalHarian,
            'total_bulanan'      => $totalBulanan,
            'total_kalender'     => $totalKalender,
            'total_foto'         => $totalFoto,
            'avg_profit_bulanan' => $avgProfitBulanan !== null ? round((float) $avgProfitBulanan, 2) : 0,
        ];
    }

    /**
     * Simpan kumpulan berkas gambar ke storage lokal dan buat record database.
     *
     * @param array<UploadedFile> $images
     * @param array<string> $captions
     */
    private function storeImages(LaporanKeuntungan $report, array $images, array $captions = [], int $startOrder = 0): void
    {
        $order = $startOrder;
        foreach ($images as $index => $file) {
            if (!($file instanceof UploadedFile) || !$file->isValid()) {
                continue;
            }

            $extension = $file->getClientOriginalExtension();
            $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . Str::random(8) . '.' . $extension;
            $path = $file->storeAs("profit_reports/{$report->id}", $filename, 'local');

            LaporanKeuntunganGambar::create([
                'laporan_keuntungan_id' => $report->id,
                'file_path'             => $path,
                'file_name'             => $file->getClientOriginalName(),
                'file_size'             => $file->getSize(),
                'mime_type'             => $file->getMimeType() ?? 'image/jpeg',
                'caption'               => $captions[$index] ?? null,
                'urutan'                => $order++,
            ]);
        }
    }
}
