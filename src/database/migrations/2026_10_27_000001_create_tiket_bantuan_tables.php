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
        Schema::create('tiket_bantuan', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor', 30)->unique(); // TKT-YYYYMM-0001
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subjek', 255);
            $table->string('status', 30)->default('open')->index(); // open, in_progress, answered, resolved, closed
            $table->timestamp('last_reply_at')->nullable()->index();
            $table->string('last_reply_by', 20)->nullable(); // member | admin
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('tiket_bantuan_balasan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tiket_bantuan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('pengirim', 20); // member | admin
            $table->text('pesan');
            $table->timestamps();

            $table->index(['tiket_id', 'created_at']);
        });

        Schema::create('tiket_bantuan_lampiran', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('balasan_id')->constrained('tiket_bantuan_balasan')->cascadeOnDelete();
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->unsignedInteger('file_size')->default(0);
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->index('balasan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiket_bantuan_lampiran');
        Schema::dropIfExists('tiket_bantuan_balasan');
        Schema::dropIfExists('tiket_bantuan');
    }
};
