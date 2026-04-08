<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\PollWooOrdersJob;
use App\Models\Integration;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Integration::query()
        ->where('platform', 'woocommerce')
        ->where('status', 'connected')
        ->chunkById(100, function ($integrations) {
            foreach ($integrations as $integration) {
                PollWooOrdersJob::dispatch($integration->id);
            }
        });
})->everyMinute();