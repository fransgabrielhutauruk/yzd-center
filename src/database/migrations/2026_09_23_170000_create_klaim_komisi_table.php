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
        Schema::create('klaim_komisi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_klaim', 30)->unique();
            $table->foreignId('affiliator_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total_downline')->default(1);
            $table->unsignedInteger('total_akun')->default(1);
            $table->decimal('total_modal_riil', 15, 2)->default(0);
            $table->decimal('total_deposit_amount', 15, 2)->default(0);
            $table->decimal('total_gross_profit', 15, 2)->nullable();
            $table->decimal('total_master_gross_share', 15, 2)->nullable();
            $table->decimal('total_master_net_share', 15, 2)->nullable();
            $table->decimal('total_investor_net_profit', 15, 2)->nullable();
            $table->decimal('total_affiliate_commission', 15, 2)->nullable();
            $table->decimal('total_profit_master', 15, 2)->nullable();
            $table->decimal('total_nominal_transfer', 15, 2)->nullable();
            $table->string('nama_bank', 100);
            $table->string('nomor_rekening', 50);
            $table->string('atas_nama_rekening', 100);
            $table->string('status', 30)->default('pending'); // pending, approved_transferred, rejected
            $table->string('bukti_transfer_path')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('affiliator_id');
            $table->index('created_at');
        });

        Schema::create('klaim_komisi_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klaim_komisi_id')->constrained('klaim_komisi')->cascadeOnDelete();
            $table->foreignId('downline_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('akun_copytrade_id')->nullable()->constrained('akun_copytrade')->nullOnDelete();
            $table->string('broker', 50);
            $table->string('nomor_akun_trading', 50);
            $table->boolean('is_akun_pertama')->default(false);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedSmallInteger('hari_kehitung')->default(90);
            $table->decimal('modal_riil', 15, 2)->default(0);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->unsignedTinyInteger('tier_level'); // 1, 2, 3, 4
            $table->unsignedTinyInteger('persentase_tier'); // 50, 40, 30, 20
            $table->decimal('roi_percentage', 8, 2)->nullable();
            $table->decimal('gross_profit', 15, 2)->nullable();
            $table->decimal('master_share_pct', 5, 2)->default(30.00);
            $table->decimal('master_gross_share', 15, 2)->nullable();
            $table->decimal('affiliate_share_pct', 5, 2)->default(20.00);
            $table->decimal('affiliate_commission', 15, 2)->nullable();
            $table->decimal('master_net_share', 15, 2)->nullable();
            $table->decimal('investor_net_profit', 15, 2)->nullable();
            $table->decimal('profit_master', 15, 2)->nullable();
            $table->decimal('nominal_transfer', 15, 2)->nullable();
            $table->timestamps();

            $table->index('klaim_komisi_id');
            $table->index('downline_id');
            $table->index('akun_copytrade_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klaim_komisi_items');
        Schema::dropIfExists('klaim_komisi');
    }
};
