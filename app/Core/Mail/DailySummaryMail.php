<?php

namespace App\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Résumé quotidien (DailySummaryService::build) : envoyé chaque soir, et affiché tel quel dans l'aperçu du
 * back office.
 */
class DailySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $summary) {}

    public function build()
    {
        return $this->subject(__('Daily summary of :date: :revenue', [
            'date' => $this->summary['date']->format('d/m/Y'),
            'revenue' => money($this->summary['revenue'], 0),
        ]))->view('core::emails.daily-summary', ['summary' => $this->summary]);
    }
}
