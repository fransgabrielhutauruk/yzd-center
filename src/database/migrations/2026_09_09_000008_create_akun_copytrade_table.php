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
        Schema::create('akun_copytrade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tier', 20); // tier_1, tier_2, tier_3
            $table->string('broker', 50); // HFM, XM
            $table->string('tipe_akun', 50); // cent, standard, private_vps
            $table->string('nomor_akun_trading', 50);
            $table->string('server_broker', 100)->nullable();
            $table->string('myfxbook_url', 255)->nullable();
            $table->text('master_password')->nullable();   // encrypted via Crypt::encryptString()
            $table->text('investor_password')->nullable(); // encrypted via Crypt::encryptString()
            $table->decimal('modal_riil', 15, 2)->unsigned();
            $table->string('surat_perjanjian_path')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('nama_bank', 100);
            $table->string('nomor_rekening', 50);
            $table->string('atas_nama_rekening', 100);
            $table->string('status', 30)->default('pending'); // pending, active, suspended
            $table->text('catatan_admin')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_copytrade');
    }
};
