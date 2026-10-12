<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PengaturanMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::table('pengaturan_master')->updateOrInsert(
            ['id' => 1],
            [
                'kurs_usd_idr'            => 16796.00,
                'kurs_usd_idr_updated_at' => now(),
                'updated_at'              => now(),
            ]
        );
    }
}
