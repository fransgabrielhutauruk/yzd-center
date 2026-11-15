<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TrashListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:trash:list {model? : Kunci modul data yang ingin ditampilkan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menampilkan daftar data terhapus (soft delete) di keranjang sampah sistem';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var array<string, array<string, mixed>> $config */
        $config = config('trash', []);
        $modelArg = $this->argument('model');

        if ($modelArg !== null) {
            if (!array_key_exists($modelArg, $config)) {
                $this->error("Modul '{$modelArg}' tidak valid. Pilihan yang tersedia: " . implode(', ', array_keys($config)));

                return Command::FAILURE;
            }

            $targets = [$modelArg => $config[$modelArg]];
        } else {
            $targets = $config;
        }

        $rows = [];

        foreach ($targets as $key => $entry) {
            $modelClass = $entry['model'];
            $displayField = $entry['display'];

            $items = $modelClass::onlyTrashed()->orderByDesc('deleted_at')->get();

            foreach ($items as $item) {
                $displayVal = Str::limit((string) ($item->{$displayField} ?? '-'), 50);

                $rows[] = [
                    $key,
                    $item->getKey(),
                    $displayVal,
                    $item->deleted_at?->format('Y-m-d H:i:s') ?? '-',
                    $item->deleted_by ?? '-',
                ];
            }
        }

        if (empty($rows)) {
            $this->info('Tidak ada data terhapus di keranjang sampah.');

            return Command::SUCCESS;
        }

        $this->table(['Modul', 'ID', 'Judul/Display', 'Deleted At', 'Deleted By'], $rows);

        return Command::SUCCESS;
    }
}
