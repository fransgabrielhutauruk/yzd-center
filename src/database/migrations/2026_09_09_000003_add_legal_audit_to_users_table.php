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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('terms_agreed_at')->nullable()->after('avatar');
            $table->string('terms_version', 10)->nullable()->default('v1.0')->after('terms_agreed_at');
            $table->string('ip_address', 45)->nullable()->after('terms_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['terms_agreed_at', 'terms_version', 'ip_address']);
        });
    }
};
