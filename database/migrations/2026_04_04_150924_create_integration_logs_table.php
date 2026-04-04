<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('integration_id')->index();
            $table->string('direction'); // inbound / outbound
            $table->string('event_type')->nullable();
            $table->string('request_url')->nullable();
            $table->longText('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->integer('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('status')->default('info'); // success, failed, info

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};