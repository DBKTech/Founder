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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();

            $table->string('order_no', 50);
            $table->string('status', 30)->default('draft');
            // draft, approved, unprint_awb, pending, on_the_move, completed, returned, rejected, cancelled
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamp('ordered_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
