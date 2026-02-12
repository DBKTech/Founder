<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('shipment_id');

            $table->string('status'); // same set as shipment status
            $table->string('description')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->json('payload')->nullable(); // raw tracking event data etc

            $table->timestamps();

            $table->index(['tenant_id', 'shipment_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};
