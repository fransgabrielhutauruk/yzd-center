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
        Schema::create('konten_legal', function (Blueprint $table) {
            $table->id();
            $table->string('key_name', 50)->unique(); // terms_conditions, privacy_policy
            $table->string('judul');
            $table->longText('konten');
            $table->string('versi', 20)->default('v1.0');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konten_legal');
    }
};
