<?php

namespace App\Console\Commands;

use App\Models\LaporanProfitBulanan;
use App\Services\AuditLogService;
use DomainException;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TrashRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:trash:restore {model : Kunci modul data} {id : ID data terhapus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memulihkan data terhapus (soft delete) dari keranjang sampah sistem';

    /**
     * Execute the console command.
     */
    public function handle(AuditLogService $auditLogService): int
    {
        /** @var array<string, array<string, mixed>> $config */
        $config = config('trash', []);
        $modelKey = (string) $this->argument('model');
        $id = (int) $this->argument('id');

        if (!array_key_exists($modelKey, $config)) {
            $this->error("Modul '{$modelKey}' tidak valid. Pilihan yang tersedia: " . implode(', ', array_keys($config)));

            return Command::FAILURE;
        }

        $entry = $config[$modelKey];
        $modelClass = $entry['model'];

        $item = $modelClass::onlyTrashed()->find($id);

        if (!$item) {
            $this->error("Data {$entry['label']} dengan ID #{$id} tidak ditemukan di keranjang sampah.");

            return Command::FAILURE;
        }

        // 1. Validasi keberadaan dan status induk (jika model ini adalah anak berelasi)
        if (!empty($entry['parent'])) {
            $parentConfig = $entry['parent'];
            $parentModel = $parentConfig['model'];
            $fk = $parentConfig['fk'];
            $parentId = $item->{$fk};

            $parent = $parentModel::withTrashed()->find($parentId);

            if (!$parent || $parent->trashed()) {
                $this->error("Tidak dapat memulihkan {$entry['label']} karena {$parentConfig['label']} induknya (#{$parentId}) masih terhapus atau tidak ditemukan. Pulihkan induknya terlebih dahulu.");

                return Command::FAILURE;
            }
        }

        // 2. Validasi bentrok periode laporan profit
        if ($modelKey === 'laporan-profit' && $item instanceof LaporanProfitBulanan) {
            $conflict = LaporanProfitBulanan::query()
                ->where('akun_copytrade_id', $item->akun_copytrade_id)
                ->where('periode_ke', $item->periode_ke)
                ->first();

            if ($conflict) {
                $this->error("Tidak dapat memulihkan laporan: sudah ada laporan aktif untuk akun #{$item->akun_copytrade_id} periode ke-{$item->periode_ke} (Nomor: {$conflict->nomor}).");

                return Command::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($item, $entry, $auditLogService): void {
                if (method_exists($item, 'restoreSlugOrFail')) {
                    $item->restoreSlugOrFail();
                }

                $item->deleted_by = null;
                $item->saveQuietly();
                $item->restore();

                $auditLogService->record(
                    kategori: $entry['category'] ?? 'edukasi_pengaturan',
                    aksi: 'pulihkan_data',
                    deskripsi: "Memulihkan data {$entry['label']} #{$item->getKey()} via Artisan CLI.",
                    subject: $item,
                    tingkat: 'info'
                );
            });
        } catch (DomainException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error("Terjadi kesalahan saat memulihkan data: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $displayTitle = (string) ($item->{$entry['display']} ?? "#{$id}");
        $this->info("Data {$entry['label']} #{$id} ('{$displayTitle}') berhasil dipulihkan.");

        return Command::SUCCESS;
    }
}
