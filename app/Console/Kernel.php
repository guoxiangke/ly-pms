<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // # Automatically removing temporary uploads
        $schedule->command('media-library:delete-old-temporary-uploads')->daily();
        $schedule->command("app:sync-content-mw")->timezone('Asia/Shanghai')->cron("15 0 * * *");
        $schedule->command("app:sync-content-cmw")->timezone('Asia/Shanghai')->cron("20 0 * * *");
        // $schedule->command("app:sync-content-it")->timezone('Asia/Shanghai')->cron("17 0 * * *");
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
