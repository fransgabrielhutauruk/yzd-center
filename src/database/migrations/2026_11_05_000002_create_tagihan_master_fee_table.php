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
        Schema::create('tagihan_master_fee', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor', 20)->unique(); // Format: MFE-YYYYMM-XXXX
            $table->foreignId('riwayat_balance_id')->unique()->constrained('riwayat_balance_copytrade')->cascadeOnDelete();
            $table->foreignId('akun_copytrade_id')->constrained('akun_copytrade')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('periode'); // Hari pertama bulan profit (bulan sebelum tanggal_pencatatan)
            $table->decimal('profit_bersih', 15, 2);
            $table->decimal('fee_persen', 5, 2);
            $table->decimal('nominal_fee', 15, 2);
            $table->date('jatuh_tempo');
            $table->string('status', 25)->default('belum_dibayar')->index();
            $table->string('bukti_transfer_path')->nullable();
            $table->timestamp('dibayar_at')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['periode', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_master_fee');
    }
};
