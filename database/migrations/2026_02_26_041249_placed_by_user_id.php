<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('placed_by_user_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['tenant_id', 'placed_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'placed_by_user_id']);
            $table->dropConstrainedForeignId('placed_by_user_id');
        });
    }
};
