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
        Schema::create('profit_master_posts', function (Blueprint $table) {
            $table->id();
            $table->text('caption');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('profit_master_post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('profit_master_posts')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('file_size')->default(0);
            $table->string('mime_type');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['post_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profit_master_post_images');
        Schema::dropIfExists('profit_master_posts');
    }
};
