<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('order_id');

            $table->string('status')->default('pending'); // pending, label_created, picked_up, in_transit, out_for_delivery, delivered, exception, cancelled, returned
            $table->string('courier_code')->nullable();   // poslaju, jnt, dhl, etc
            $table->string('service_code')->nullable();   // optional
            $table->string('tracking_number')->nullable();
            $table->text('label_url')->nullable();

            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->json('meta')->nullable(); // store courier API response etc

            $table->timestamps();

            // indexes
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'courier_code']);
            $table->unique(['order_id']); // 1 shipment per order for now (can remove later)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
