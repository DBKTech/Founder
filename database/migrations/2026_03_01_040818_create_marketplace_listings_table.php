<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('status')->default('draft')->index(); // draft|published|archived
            $table->string('visibility')->default('tenant'); // tenant|public|hidden (future)
            $table->timestamp('published_at')->nullable();

            $table->boolean('woo_sync_enabled')->default(false);
            $table->unsignedBigInteger('woo_product_id')->nullable();
            $table->timestamp('woo_last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};