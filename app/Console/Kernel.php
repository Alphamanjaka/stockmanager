<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Sauvegarde quotidienne à 01:00 du matin
        // withoutOverlapping : Evite de lancer 2 backups si le précédent n'est pas fini
        // appendOutputTo : Logue le résultat pour le débogage
        $schedule->command('app:run-backup')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/backup.log'));

        // Nettoyage des vieilles sauvegardes tous les dimanches à 02:00
        $schedule->command('backup:clean')
            ->weekly()
            ->sundays()
            ->at('02:00');

        // Surveillance des chutes de stock (quotidien à 08:00)
        $schedule->command('stock:monitor-drop --threshold=15')
            ->dailyAt('08:00');
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
