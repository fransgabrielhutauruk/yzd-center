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
        Schema::table('pengaturan_master', function (Blueprint $table) {
            if (!Schema::hasColumn('pengaturan_master', 'tier_1_desc')) {
                $table->text('tier_1_desc')->nullable()->after('tier_3_min');
            }
            if (!Schema::hasColumn('pengaturan_master', 'tier_2_desc')) {
                $table->text('tier_2_desc')->nullable()->after('tier_1_desc');
            }
            if (!Schema::hasColumn('pengaturan_master', 'tier_3_desc')) {
                $table->text('tier_3_desc')->nullable()->after('tier_2_desc');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan_master', function (Blueprint $table) {
            $table->dropColumn(['tier_1_desc', 'tier_2_desc', 'tier_3_desc']);
        });
    }
};
