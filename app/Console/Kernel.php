<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Scheduling is registered in routes/console.php for the current Laravel app structure.
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
