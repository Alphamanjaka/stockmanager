<?php

namespace App\Core\Console\Commands;

use App\Core\Services\DailySummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

/**
 * Envoi du résumé quotidien par e-mail.
 *
 * Planifiée toutes les heures (routes/console.php) avec --scheduled : n'envoie que si le résumé est activé,
 * à l'heure choisie dans les réglages (heure de la boutique), et une seule fois par jour. Sans --scheduled,
 * envoie tout de suite.
 */
class SendDailySummary extends Command
{
    protected $signature = 'reports:daily-summary
                            {--date= : Jour résumé (AAAA-MM-JJ), aujourd\'hui par défaut}
                            {--scheduled : Envoi planifié : respecte l\'activation, l\'heure et un envoi par jour}';

    protected $description = 'Envoie le résumé quotidien (ventes, marge, ruptures) par e-mail';

    public function handle(DailySummaryService $summary): int
    {
        // Même langue que l'application (réglage app_locale), comme dans le navigateur
        $locale = settings('app_locale');
        if (array_key_exists((string) $locale, config('app.supported_locales'))) {
            App::setLocale($locale);
        }

        $now = $summary->now();
        if ($this->option('scheduled')) {
            if (! $summary->isEnabled() || $now->hour !== $summary->hour() || $summary->lastSentAt()?->isSameDay($now)) {
                return self::SUCCESS;
            }
        }

        $date = $this->option('date') ? Carbon::parse($this->option('date'), $summary->timezone()) : $now;
        $recipients = $summary->send($date);

        if ($recipients === []) {
            $this->warn('Aucun destinataire : ajoutez une adresse ou un administrateur avec une adresse e-mail.');

            return self::SUCCESS;
        }

        if ($this->option('scheduled')) {
            $summary->markSent($now);
        }
        $this->info('Résumé du '.$date->format('d/m/Y').' envoyé à '.implode(', ', $recipients).'.');

        return self::SUCCESS;
    }
}
