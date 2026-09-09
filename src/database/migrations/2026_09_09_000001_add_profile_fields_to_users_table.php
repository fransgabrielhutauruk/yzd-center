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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('role')->default('investor')->after('email'); // administrator, investor, affiliator
            $table->string('nik', 16)->nullable()->after('password');
            $table->string('phone', 20)->nullable()->after('nik');
            $table->text('alamat')->nullable()->after('phone');
            $table->string('referral_code', 32)->unique()->nullable()->after('alamat');
            $table->boolean('is_active')->default(true)->after('referral_code');
            $table->string('avatar')->nullable()->after('is_active');

            // Audit columns
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();

            // Indexes for frequent queries
            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'username',
                'role',
                'nik',
                'phone',
                'alamat',
                'referral_code',
                'is_active',
                'avatar',
                'created_by',
                'updated_by',
                'deleted_by',
                'deleted_at',
            ]);
        });
    }
};
