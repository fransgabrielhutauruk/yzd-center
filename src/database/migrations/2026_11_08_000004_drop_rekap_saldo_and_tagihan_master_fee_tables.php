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
        Schema::dropIfExists('tagihan_master_fee');
        Schema::dropIfExists('riwayat_balance_copytrade');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('riwayat_balance_copytrade')) {
            Schema::create('riwayat_balance_copytrade', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('akun_copytrade_id')->constrained('akun_copytrade')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('tanggal_pencatatan');
                $table->decimal('balance', 15, 2)->unsigned();
                $table->decimal('penarikan', 15, 2)->default(0.00);
                $table->decimal('setoran_tambahan', 15, 2)->default(0.00);
                $table->decimal('keuntungan_nominal', 15, 2)->default(0.00);
                $table->decimal('keuntungan_persen', 8, 2)->default(0.00);
                $table->decimal('keuntungan_kumulatif', 15, 2)->default(0.00);
                $table->decimal('pertumbuhan_kumulatif_persen', 8, 2)->default(0.00);
                $table->string('catatan', 255)->nullable();
                $table->string('bukti_saldo_path')->nullable();
                $table->timestamps();

                $table->unique(['akun_copytrade_id', 'tanggal_pencatatan'], 'unique_akun_tanggal_balance');
                $table->index(['user_id', 'tanggal_pencatatan']);
                $table->index('tanggal_pencatatan');
            });
        }

        if (!Schema::hasTable('tagihan_master_fee')) {
            Schema::create('tagihan_master_fee', function (Blueprint $table): void {
                $table->id();
                $table->string('nomor', 20)->unique();
                $table->foreignId('riwayat_balance_id')->unique()->constrained('riwayat_balance_copytrade')->cascadeOnDelete();
                $table->foreignId('akun_copytrade_id')->constrained('akun_copytrade')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('periode');
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
    }
};
