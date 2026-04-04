<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'source')) {
                $table->string('source')->nullable()->index();
            }

            if (! Schema::hasColumn('orders', 'external_id')) {
                $table->string('external_id')->nullable()->index();
            }

            if (! Schema::hasColumn('orders', 'meta')) {
                $table->json('meta')->nullable();
            }

            $table->index(['tenant_id', 'source', 'external_id'], 'orders_tenant_source_external_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_tenant_source_external_idx');
        });
    }
};