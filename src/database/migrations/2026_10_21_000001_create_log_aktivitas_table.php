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
        Schema::create('log_aktivitas', function (Blueprint $table): void {
            $table->id();

            // Identitas Pelaku (dengan snapshot agar histori tetap valid jika user dihapus)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150)->nullable();
            $table->string('user_username', 100)->nullable();
            $table->string('user_role', 30)->nullable(); // administrator, affiliator, investor, guest

            // Kategori, Aksi & Tingkat Kepentingan
            $table->string('kategori', 40); // autentikasi, kyc_dokumen, copytrade, komisi_afiliasi, pengguna, undangan, edukasi_pengaturan
            $table->string('aksi', 50);     // login, login_gagal, logout, register, ganti_password, dll.
            $table->string('tingkat', 20)->default('info'); // info, warning, critical

            // Deskripsi naratif dalam Bahasa Indonesia
            $table->text('deskripsi');

            // Objek Target Aktivitas (Polymorphic)
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Rincian Perubahan (Sebelum vs Sesudah) & Metadata Tambahan
            $table->json('perubahan')->nullable();

            // Jejak Jaringan & Perangkat
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Audit log hanya append-only (hanya mencatat created_at)
            $table->timestamp('created_at')->nullable()->index();

            // Indeks untuk filter & query kecepatan tinggi
            $table->index(['kategori', 'created_at']);
            $table->index(['user_role', 'created_at']);
            $table->index(['tingkat', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
