<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_balance_copytrade', function (Blueprint $table): void {
            $table->id();

            // Relasi Akun Copytrade & Investor
            $table->foreignId('akun_copytrade_id')->constrained('akun_copytrade')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Data Saldo & Tanggal Pencatatan (Default setiap tanggal 1)
            $table->date('tanggal_pencatatan');
            $table->decimal('balance', 15, 2)->unsigned();

            // Otomatisasi Perhitungan Keuntungan
            // Keuntungan dibanding input sebelumnya (atau modal awal jika input pertama)
            $table->decimal('keuntungan_nominal', 15, 2)->default(0.00);
            $table->decimal('keuntungan_persen', 8, 2)->default(0.00);

            // Akumulasi Keuntungan All-Time (Balance - Modal Awal Investasi)
            $table->decimal('keuntungan_kumulatif', 15, 2)->default(0.00);
            $table->decimal('pertumbuhan_kumulatif_persen', 8, 2)->default(0.00);

            // Keterangan / Catatan Tambahan (Misal: ada penarikan parsial)
            $table->string('catatan', 255)->nullable();

            $table->timestamps();

            // Index dan batasan keunikan per tanggal pencatatan
            $table->unique(['akun_copytrade_id', 'tanggal_pencatatan'], 'unique_akun_tanggal_balance');
            $table->index(['user_id', 'tanggal_pencatatan']);
            $table->index('tanggal_pencatatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_balance_copytrade');
    }
};
