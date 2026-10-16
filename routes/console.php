<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérifie la valeur du stock tous les jours à 18h00.
// Le seuil peut être ajusté ici (--threshold=15 pour 15%).
Schedule::command('stock:monitor-drop --threshold=10')->dailyAt('18:00');

// Sauvegarde quotidienne (fichiers + base) à 01:00, traitée par le worker de file d'attente.
// withoutOverlapping : évite de lancer deux sauvegardes si la précédente n'est pas terminée.
Schedule::command('app:run-backup')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Suppression des anciennes sauvegardes selon les règles de config/backup.php, le dimanche à 02:00.
Schedule::command('backup:clean')->weekly()->sundays()->at('02:00');
