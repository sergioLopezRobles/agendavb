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

        // 2. Respaldo Excel Plan Avanzado (Se ejecuta DIARIO a las 12:00 de la noche)
        $schedule->command('app:respaldocalendario --plan=3')->dailyAt('00:00');

        // 3. Respaldo Excel Plan Medio (Se ejecuta CADA 3 DÍAS a las 12:00 de la noche)
        $schedule->command('app:respaldocalendario --plan=2')->cron('0 0 */3 * *');
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
