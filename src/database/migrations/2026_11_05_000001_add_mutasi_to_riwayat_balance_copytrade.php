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
        Schema::table('riwayat_balance_copytrade', function (Blueprint $table): void {
            $table->decimal('penarikan', 15, 2)->default(0.00)->after('balance');
            $table->decimal('setoran_tambahan', 15, 2)->default(0.00)->after('penarikan');
            $table->string('bukti_saldo_path')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_balance_copytrade', function (Blueprint $table): void {
            $table->dropColumn(['penarikan', 'setoran_tambahan', 'bukti_saldo_path']);
        });
    }
};
