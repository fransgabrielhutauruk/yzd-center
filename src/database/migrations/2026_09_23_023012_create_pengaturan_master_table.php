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
        Schema::create('pengaturan_master', function (Blueprint $table) {
            $table->id();
            $table->string('link_referral_broker')->nullable();
            $table->string('link_master_akun')->nullable(); // MQL5 or direct link
            $table->decimal('tier_1_min', 15, 2)->default(0);
            $table->decimal('tier_2_min', 15, 2)->default(5000);
            $table->decimal('tier_3_min', 15, 2)->default(300000);
            $table->text('tier_1_desc')->nullable();
            $table->text('tier_2_desc')->nullable();
            $table->text('tier_3_desc')->nullable();
            $table->string('tier_1_pdf_path')->nullable();
            $table->string('tier_2_pdf_path')->nullable();
            $table->string('tier_3_pdf_path')->nullable();
            $table->decimal('kurs_usd_idr', 15, 2)->default(16796.00);
            $table->timestamp('kurs_usd_idr_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_master');
    }
};
