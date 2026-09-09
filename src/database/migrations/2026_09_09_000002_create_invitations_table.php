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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id('invitation_id');
            $table->string('token', 64)->unique();
            $table->string('label');
            $table->string('target_role')->default('investor'); // investor | affiliator
            $table->unsignedInteger('kuota_maksimal')->default(1);
            $table->unsignedInteger('total_terpakai')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expired_at')->nullable();

            // Audit columns
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('expired_at');
            $table->index('target_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
