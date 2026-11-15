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
        Schema::create('laporan_profit_bulanan', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor', 20)->unique(); // MFE-YYYYMM-XXXX
            $table->foreignId('akun_copytrade_id')->constrained('akun_copytrade')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('periode_ke'); // 1, 2, 3, ...
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->date('jatuh_tempo'); // tanggal 1 bulan berikutnya (penanda tampilan)
            $table->decimal('profit_usd', 15, 2); // boleh negatif
            $table->string('bukti_myfxbook_path');
            $table->decimal('wd_usd', 15, 2)->nullable();
            $table->decimal('wd_idr', 18, 0)->nullable();
            $table->string('bukti_wd_path')->nullable();
            $table->decimal('kurs_riil', 15, 2)->nullable();
            $table->decimal('fee_persen', 5, 2);
            $table->decimal('fee_usd', 15, 2)->default(0);
            $table->decimal('fee_idr', 18, 0)->default(0);
            $table->string('status', 25)->default('belum_dibayar')->index();
            $table->string('bukti_transfer_path')->nullable();
            $table->timestamp('dibayar_at')->nullable();
            $table->text('catatan_admin')->nullable(); // alasan tolak
            $table->boolean('perlu_koreksi')->default(false);
            $table->text('catatan_koreksi')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('deleted_by', 100)->nullable();

            $table->index(['akun_copytrade_id', 'periode_ke']);
            $table->index(['periode_akhir', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_profit_bulanan');
    }
};
