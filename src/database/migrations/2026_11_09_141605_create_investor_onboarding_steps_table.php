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
        Schema::create('investor_onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('modal_usd', 15, 2)->nullable();
            $table->string('tier', 20)->nullable(); // tier_1, tier_2, tier_3
            $table->json('completed_steps')->nullable();
            $table->json('draft_data')->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress, submitted
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_onboarding_steps');
    }
};
