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
        Schema::create('klaim_referral', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('downline_id')->constrained('users')->cascadeOnDelete();
            $table->string('bukti_chat_path')->nullable();
            $table->text('alasan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['affiliator_id', 'status']);
            $table->index(['downline_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klaim_referral');
    }
};
