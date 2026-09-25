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
        Schema::create('laporan_keuntungan', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['harian', 'bulanan'])->index();
            $table->string('judul');
            $table->date('tanggal')->nullable()->index();
            $table->unsignedTinyInteger('bulan')->nullable()->index();
            $table->unsignedSmallInteger('tahun')->nullable()->index();
            $table->decimal('profit_persen', 8, 2)->nullable();
            $table->decimal('profit_nominal_usd', 15, 2)->nullable();
            $table->boolean('is_kalender')->default(false)->index();
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['published', 'draft'])->default('published')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('laporan_keuntungan_gambar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_keuntungan_id')->constrained('laporan_keuntungan')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('file_size')->default(0);
            $table->string('mime_type')->default('image/png');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_keuntungan_gambar');
        Schema::dropIfExists('laporan_keuntungan');
    }
};
