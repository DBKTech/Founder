<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->after('status');
            $table->string('payment_status', 30)->default('unpaid')->after('payment_method');
            $table->string('payment_gateway', 50)->nullable()->after('payment_status');
            $table->string('payment_ref', 100)->nullable()->after('payment_gateway');
            $table->string('bank_transfer_proof_path')->nullable()->after('payment_ref');
            $table->timestamp('paid_at')->nullable()->after('bank_transfer_proof_path');
            $table->text('payment_notes')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'payment_gateway',
                'payment_ref',
                'bank_transfer_proof_path',
                'paid_at',
                'payment_notes',
            ]);
        });
    }
};