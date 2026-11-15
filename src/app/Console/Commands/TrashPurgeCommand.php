<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TrashPurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:trash:purge 
                            {--days= : Batas usia hapus dalam hari (misal 30 atau 0 untuk semua)}
                            {--model= : Kunci modul spesifik yang ingin dibersihkan}
                            {--force : Jalankan pembersihan tanpa konfirmasi interaktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus permanen (purge) data terhapus dan berkas fisiknya dari sistem';

    /**
     * Urutan aman pembersihan agar anak dibersihkan sebelum induk.
     *
     * @var array<int, string>
     */
    private const PURGE_ORDER = [
        'panduan-artikel',
        'panduan-kategori',
        'faq',
        'faq-topik',
        'profit-master-foto',
        'profit-master',
        'laporan-profit',
        'glosarium',
        'undangan',
    ];

    /**
     * Execute the console command.
     */
    public function handle(AuditLogService $auditLogService): int
    {
        $daysOption = $this->option('days');

        if ($daysOption === null || $daysOption === '') {
            $this->error('Parameter --days wajib diisi secara eksplisit untuk mencegah penghapusan data tidak disengaja. Contoh: --days=30 atau --days=0');

            return Command::FAILURE;
        }

        $days = (int) $daysOption;
        if ($days < 0) {
            $this->error('Nilai --days tidak boleh bernilai negatif.');

            return Command::FAILURE;
        }

        $cutoff = $days === 0 ? Carbon::now() : Carbon::now()->subDays($days);

        /** @var array<string, array<string, mixed>> $config */
        $config = config('trash', []);
        $modelArg = $this->option('model');

        if ($modelArg !== null) {
            if (!array_key_exists($modelArg, $config)) {
                $this->error("Modul '{$modelArg}' tidak valid. Pilihan yang tersedia: " . implode(', ', array_keys($config)));

                return Command::FAILURE;
            }

            $orderedKeys = [$modelArg];
        } else {
            // Urutkan kunci sesuai PURGE_ORDER
            $orderedKeys = array_values(array_intersect(self::PURGE_ORDER, array_keys($config)));
            // Tambahkan kunci lain yang mungkin belum terdaftar di PURGE_ORDER
            foreach (array_keys($config) as $k) {
                if (!in_array($k, $orderedKeys, true)) {
                    $orderedKeys[] = $k;
                }
            }
        }

        // Hitung total item yang memenuhi syarat
        $summary = [];
        $totalEligible = 0;

        foreach ($orderedKeys as $key) {
            $entry = $config[$key];
            $modelClass = $entry['model'];

            $count = $modelClass::onlyTrashed()->where('deleted_at', '<=', $cutoff)->count();
            if ($count > 0) {
                $summary[$key] = [
                    'label' => $entry['label'],
                    'count' => $count,
                ];
                $totalEligible += $count;
            }
        }

        if ($totalEligible === 0) {
            $this->info("Tidak ada data terhapus yang memenuhi kriteria purge (terhapus >= {$days} hari).");

            return Command::SUCCESS;
        }

        $this->warn("Ditemukan {$totalEligible} data terhapus yang akan dibersihkan secara permanen:");
        foreach ($summary as $key => $info) {
            $this->line("- {$info['label']} ({$key}): {$info['count']} data");
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Apakah Anda yakin ingin melanjutkan pembersihan permanen ini? Data dan berkas fisik tidak dapat dipulihkan.", false)) {
                $this->info('Pembersihan data dibatalkan.');

                return Command::SUCCESS;
            }
        }

        $disk = Storage::disk('local');
        $totalPurged = 0;

        foreach ($orderedKeys as $key) {
            if (!isset($summary[$key])) {
                continue;
            }

            $entry = $config[$key];
            $modelClass = $entry['model'];
            $items = $modelClass::onlyTrashed()->where('deleted_at', '<=', $cutoff)->get();
            $purgedForModel = 0;

            foreach ($items as $item) {
                // Hapus berkas fisik jika ada
                if (is_callable($entry['files'] ?? null)) {
                    ($entry['files'])($item, $disk);
                } elseif (is_array($entry['files'] ?? null)) {
                    foreach ($entry['files'] as $field) {
                        $filePath = $item->{$field} ?? null;
                        if ($filePath && $disk->exists($filePath)) {
                            $disk->delete($filePath);
                        }
                    }
                }

                $item->forceDelete();
                $purgedForModel++;
                $totalPurged++;
            }

            if ($purgedForModel > 0) {
                $auditLogService->record(
                    kategori: $entry['category'] ?? 'edukasi_pengaturan',
                    aksi: 'bersihkan_data',
                    deskripsi: "Pembersihan permanen (purge) {$purgedForModel} data {$entry['label']} via Artisan CLI.",
                    tingkat: 'warning'
                );
            }
        }

        $this->info("Pembersihan permanen selesai: Berhasil menghapus {$totalPurged} data beserta berkas terkait.");

        return Command::SUCCESS;
    }
}
