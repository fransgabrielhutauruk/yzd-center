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
        Schema::table('klaim_komisi_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('master_gross_share_idr')->default(0)->after('master_gross_share');
            $table->unsignedBigInteger('affiliate_commission_idr')->default(0)->after('affiliate_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('klaim_komisi_items', function (Blueprint $table): void {
            $table->dropColumn(['master_gross_share_idr', 'affiliate_commission_idr']);
        });
    }
};
