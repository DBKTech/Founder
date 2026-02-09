<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->nullOnDelete();

            $table->string('user_type', 20)
                ->default('tenant_user')
                ->after('tenant_id'); // platform_admin | tenant_user

            $table->boolean('is_active')
                ->default(true)
                ->after('password');

            $table->index(['tenant_id']);
            $table->index(['user_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['user_type']);

            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['user_type', 'is_active']);
        });
    }
};
