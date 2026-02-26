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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->string('status', 20)->default('draft'); // draft|active
            $table->string('sku')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_at_price', 10, 2)->nullable();

            $table->string('primary_image_path')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
