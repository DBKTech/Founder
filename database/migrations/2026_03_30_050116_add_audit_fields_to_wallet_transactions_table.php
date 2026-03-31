<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->decimal('balance_before', 12, 2)->default(0)->after('amount');
            $table->decimal('balance_after', 12, 2)->default(0)->after('balance_before');
            $table->string('status', 30)->default('posted')->after('remarks');
            $table->json('meta')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'balance_before',
                'balance_after',
                'status',
                'meta',
            ]);
        });
    }
};