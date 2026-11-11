<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar tabel edukasi dan pusat bantuan yang mendapatkan soft delete.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'edukasi_glosarium',
        'panduan_kategori',
        'panduan_bantuan',
        'edukasi_faq_topik',
        'edukasi_faq',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
                $table->string('deleted_by', 100)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['deleted_at', 'deleted_by']);
            });
        }
    }
};
