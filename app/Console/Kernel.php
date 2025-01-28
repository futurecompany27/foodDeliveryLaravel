<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        Commands\SendOrderReminders::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('orders:send-order-reminders')->daily();
        $schedule->command('check:suborders')->daily();
        $schedule->command('app:check-suborder-delivery')->daily();
        $schedule->command('chefs:check-non-taxable')->lastDayOfMonth();
        // Run the check for non-taxable chefs daily
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
