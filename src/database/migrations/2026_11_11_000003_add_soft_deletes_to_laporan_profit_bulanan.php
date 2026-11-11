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
        Schema::table('laporan_profit_bulanan', function (Blueprint $table): void {
            $table->softDeletes();
            $table->string('deleted_by', 100)->nullable();
            // Tambahkan index biasa terlebih dahulu agar FK MySQL tetap terlindungi
            $table->index(['akun_copytrade_id', 'periode_ke']);
            $table->dropUnique(['akun_copytrade_id', 'periode_ke']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Bersihkan data terhapus yang berpotensi menyebabkan duplikasi sebelum mengembalikan unique constraint
        DB::table('laporan_profit_bulanan')
            ->whereNotNull('deleted_at')
            ->delete();

        Schema::table('laporan_profit_bulanan', function (Blueprint $table): void {
            $table->unique(['akun_copytrade_id', 'periode_ke']);
            $table->dropIndex(['akun_copytrade_id', 'periode_ke']);
            $table->dropColumn(['deleted_at', 'deleted_by']);
        });
    }
};
