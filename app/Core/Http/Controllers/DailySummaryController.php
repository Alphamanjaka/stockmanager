<?php

namespace App\Core\Http\Controllers;

use App\Core\Mail\DailySummaryMail;
use App\Core\Services\DailySummaryService;
use App\Core\Services\SettingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Résumé quotidien dans le back office : aperçu de l'e-mail pour n'importe quel jour (sans rien envoyer),
 * réglages de l'envoi automatique, et e-mail d'essai pour vérifier que la messagerie fonctionne.
 */
class DailySummaryController extends Controller
{
    public function __construct(
        private DailySummaryService $summary,
        private SettingService $settings,
    ) {}

    public function index(Request $request)
    {
        $date = $this->date($request);

        return view('core::daily-summary.index', [
            'date' => $date,
            'enabled' => $this->summary->isEnabled(),
            'hour' => $this->summary->hour(),
            'recipientsSetting' => (string) $this->settings->get(DailySummaryService::RECIPIENTS, ''),
            'recipients' => $this->summary->recipients(),
            'lastSentAt' => $this->summary->lastSentAt(),
            // Pilotes qui n'envoient rien : les e-mails sont écrits dans le journal ou gardés en mémoire
            'mailer' => config('mail.default'),
            'fakeMailer' => in_array(config('mail.default'), ['log', 'array'], true),
        ]);
    }

    /** L'e-mail du jour tel qu'il sera reçu (affiché dans la page, dans un cadre). */
    public function preview(Request $request)
    {
        return response((new DailySummaryMail($this->summary->build($this->date($request))))->render());
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'hour' => 'required|integer|min:0|max:23',
            'recipients' => [
                'nullable', 'string', 'max:1000',
                function ($attribute, $value, $fail) {
                    $invalid = array_filter(DailySummaryService::parseRecipients((string) $value), fn ($email) => ! filter_var($email, FILTER_VALIDATE_EMAIL));
                    if ($invalid) {
                        $fail(__('Invalid e-mail address: :emails', ['emails' => implode(', ', $invalid)]));
                    }
                },
            ],
        ]);

        $this->settings->updateSettings([
            DailySummaryService::ENABLED => $request->boolean('enabled') ? '1' : '0',
            DailySummaryService::HOUR => (string) $validated['hour'],
            DailySummaryService::RECIPIENTS => implode(', ', DailySummaryService::parseRecipients((string) ($validated['recipients'] ?? ''))),
        ]);

        return back()->with('success', __('Daily summary settings saved.'));
    }

    /** Envoie le résumé du jour affiché à l'utilisateur connecté seulement ; une erreur de messagerie est montrée telle quelle. */
    public function sendTest(Request $request)
    {
        $email = $request->user()->email;
        if (! $email) {
            return back()->with('error', __('Your account has no e-mail address.'));
        }

        try {
            $this->summary->send($this->date($request), [$email]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('The e-mail could not be sent: :message', ['message' => $e->getMessage()]));
        }

        return back()->with('success', __('Test e-mail sent to :email.', ['email' => $email]));
    }

    /** Jour demandé (?date=AAAA-MM-JJ), aujourd'hui par défaut ; jamais dans le futur. */
    private function date(Request $request): Carbon
    {
        $date = rescue(fn () => $request->filled('date') ? Carbon::createFromFormat('Y-m-d', $request->input('date')) : null, null, false);

        return $date && $date->lte(now()) ? $date : now();
    }
}
