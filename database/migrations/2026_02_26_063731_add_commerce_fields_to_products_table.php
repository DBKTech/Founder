<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            // Product Type (Unit / Bundle / Service)
            $table->string('product_type', 20)
                ->default('unit')
                ->after('brand_id');

            // Weight
            $table->decimal('weight', 10, 2)
                ->nullable()
                ->after('price');

            $table->string('weight_unit', 10)
                ->default('g')
                ->after('weight');

            // Max unit per purchase
            $table->unsignedInteger('max_units_per_purchase')
                ->nullable()
                ->after('weight_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'weight',
                'weight_unit',
                'max_units_per_purchase',
            ]);
        });
    }
};