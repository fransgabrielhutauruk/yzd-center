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
        // 1. Tabel Glosarium Istilah Forex
        Schema::create('edukasi_glosarium', function (Blueprint $table) {
            $table->id();
            $table->string('istilah', 100);
            $table->text('definisi');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('istilah');
        });

        // 2. Tabel Kategori Topik Pusat Bantuan
        Schema::create('panduan_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('slug', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('icon', 50)->default('HelpCircle');
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'urutan']);
        });

        // 3. Tabel Artikel Panduan & Video Bantuan
        Schema::create('panduan_bantuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('panduan_kategori')->cascadeOnDelete();
            $table->string('judul', 255);
            $table->string('slug', 255)->unique();
            $table->text('ringkasan')->nullable();
            $table->text('konten'); // format panduan bertahap / markdown
            $table->string('video_url', 255)->nullable(); // YouTube video URL / ID
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kategori_id', 'is_published']);
            $table->index('slug');
        });

        // 4. Tabel Topik FAQ (Kategori Topik Tanya Jawab)
        Schema::create('edukasi_faq_topik', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'urutan']);
        });

        // 5. Tabel Tanya Jawab (FAQ) - Dipisah dari Pusat Bantuan & Tanpa Slug
        Schema::create('edukasi_faq', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topik_id')->constrained('edukasi_faq_topik')->cascadeOnDelete();
            $table->string('pertanyaan', 255);
            $table->text('jawaban');
            $table->integer('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['topik_id', 'is_active', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edukasi_faq');
        Schema::dropIfExists('edukasi_faq_topik');
        Schema::dropIfExists('panduan_bantuan');
        Schema::dropIfExists('panduan_kategori');
        Schema::dropIfExists('edukasi_glosarium');
    }
};
