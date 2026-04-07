<?php

namespace App\Console;

use App\Jobs\PollWooOrdersJob;
use App\Models\Integration;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Integration::query()
                ->where('platform', 'woocommerce')
                ->where('status', 'connected')
                ->chunkById(100, function ($integrations) {
                    foreach ($integrations as $integration) {
                        PollWooOrdersJob::dispatch($integration->id);
                    }
                });
        })->everyMinute()->withoutOverlapping();
    }
}