<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('method', 30); // fpx, card, wallet, bank_transfer
            $table->string('gateway', 50)->nullable(); // manual, wallet, billplz, stripe, etc
            $table->string('status', 30)->default('pending'); // pending, awaiting_verification, paid, failed, refunded
            $table->string('currency', 5)->default('MYR');

            $table->decimal('amount', 12, 2)->default(0);

            $table->string('reference', 100)->nullable(); // manual ref / internal ref
            $table->string('gateway_reference', 100)->nullable(); // provider bill/txn ref
            $table->string('gateway_payment_id', 100)->nullable();
            $table->string('gateway_url')->nullable();

            $table->string('bank_transfer_proof_path')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->json('meta')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'method', 'status']);
            $table->index(['gateway', 'gateway_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};