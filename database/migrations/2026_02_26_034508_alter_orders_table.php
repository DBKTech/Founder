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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 30)->default('marketplace'); // marketplace|woocommerce
            $table->string('external_id')->nullable(); // woo order id

            $table->string('currency', 5)->default('MYR');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);

            $table->index(['tenant_id', 'source']);
            $table->index(['tenant_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
