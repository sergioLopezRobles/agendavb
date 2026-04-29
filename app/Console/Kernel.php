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
        // 1. Recordatorios de WhatsApp (Se ejecuta CADA MINUTO)
        $schedule->command('app:recordatorioscitaswhatsapp')->everyMinute();

        // 2. Esto ejecuta el respaldo todos los días exactamente a las 12:00 PM (Mediodía)
        $schedule->command('app:respaldocalendario')->dailyAt('00:00');

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
