<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar tabel materi promosi profit master yang mendapatkan soft delete.
     *
     * @var array<int, string>
     */
    private array $tables = [
        'profit_master_posts',
        'profit_master_post_images',
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
