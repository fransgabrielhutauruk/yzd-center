<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengaturan_master_fee', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_bank', 100)->nullable();
            $table->string('nomor_rekening', 100)->nullable();
            $table->string('atas_nama', 150)->nullable();
            $table->unsignedTinyInteger('fee_persen')->default(30);
            $table->timestamps();
        });

        DB::table('pengaturan_master_fee')->insert([
            'nama_bank'      => 'BCA',
            'nomor_rekening' => '1234567890',
            'atas_nama'      => 'Master YZD Trading',
            'fee_persen'     => 30,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_master_fee');
    }
};
