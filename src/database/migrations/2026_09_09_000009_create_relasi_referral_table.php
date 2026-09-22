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
        Schema::create('relasi_referral', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('downline_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('sumber', ['link_otomatis', 'klaim_manual'])->default('link_otomatis');
            $table->timestamp('joined_at')->useCurrent();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamps();

            $table->index(['affiliator_id', 'downline_id']);
            $table->index(['affiliator_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relasi_referral');
    }
};
