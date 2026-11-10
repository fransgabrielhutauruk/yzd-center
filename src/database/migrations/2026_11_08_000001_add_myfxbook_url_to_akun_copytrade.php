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
        Schema::table('akun_copytrade', function (Blueprint $table): void {
            $table->string('myfxbook_url', 255)->nullable()->after('server_broker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akun_copytrade', function (Blueprint $table): void {
            $table->dropColumn('myfxbook_url');
        });
    }
};
