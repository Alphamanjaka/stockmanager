<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Surveille les pertes non expliquées des dernières 24 heures (corrections en sortie, écarts d'inventaire
// négatifs) tous les jours à 18h00. Alerte à partir de 1 % de la valeur du stock (--threshold=2 pour 2 %).
Schedule::command('stock:monitor-drop --threshold=1 --hours=24')->dailyAt('18:00');

// Résumé quotidien par e-mail : vérifié chaque heure, envoyé une fois par jour à l'heure choisie
// (Tableau de bord › Résumé du jour), seulement s'il est activé.
Schedule::command('reports:daily-summary --scheduled')->hourly();

// Sauvegarde quotidienne (fichiers + base) à 01:00, traitée par le worker de file d'attente.
// withoutOverlapping : évite de lancer deux sauvegardes si la précédente n'est pas terminée.
Schedule::command('app:run-backup')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Suppression des anciennes sauvegardes selon les règles de config/backup.php, le dimanche à 02:00.
Schedule::command('backup:clean')->weekly()->sundays()->at('02:00');
