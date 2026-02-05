<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérifie la valeur du stock tous les jours à 18h00.
// Le seuil peut être ajusté ici (--threshold=15 pour 15%).
Schedule::command('stock:monitor-drop --threshold=10')->dailyAt('18:00');
