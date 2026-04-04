<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('platform'); // woocommerce
            $table->string('store_name')->nullable();
            $table->string('store_url');
            $table->text('api_key')->nullable();      // encrypted
            $table->text('api_secret')->nullable();   // encrypted
            $table->string('webhook_secret')->nullable();

            $table->string('status')->default('disconnected'); // connected, failed, disabled
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'platform', 'store_url'], 'integrations_unique_store');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};