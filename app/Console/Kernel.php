<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // On planifie votre commande tous les jours à minuit
        $schedule->command('app:run-backup')->daily();

        // Optionnel : On nettoie les vieux fichiers une fois par semaine
        $schedule->command('backup:clean')->weekly();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
