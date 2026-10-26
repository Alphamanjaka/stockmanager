<?php

namespace App\Core\Services;

use App\Core\Mail\DailySummaryMail;
use App\Models\User;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleItem;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Stock\Services\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Résumé quotidien : les chiffres d'une journée (ventes, marge, encaissements, meilleurs produits, vendeurs)
 * et ce qui demande une action (ruptures, commandes à recevoir, pertes non expliquées).
 *
 * Le même résumé est affiché dans le back office (aperçu, jours passés) et envoyé par e-mail chaque soir
 * (commande reports:daily-summary), aux destinataires des réglages ou, à défaut, aux administrateurs.
 */
class DailySummaryService
{
    /** Réglages (table settings). */
    public const ENABLED = 'daily_summary_enabled';

    public const HOUR = 'daily_summary_hour';

    public const RECIPIENTS = 'daily_summary_recipients';

    public const LAST_SENT = 'daily_summary_last_sent_at';

    public const DEFAULT_HOUR = 20;

    public function __construct(
        protected SaleService $saleService,
        protected PurchaseService $purchaseService,
        protected StockService $stockService,
        protected SettingService $settings,
    ) {}

    /**
     * @return array{
     *     date: Carbon, revenue: float, refunds: float, sales_count: int, average: float,
     *     previous: array{date: Carbon, revenue: float, change: ?float},
     *     margin: array{margin: float, rate: ?float, excluded: int},
     *     payments: list<array{method: string, operator: ?string, count: int, total: float}>,
     *     top_products: list<array{label: string, quantity: int, revenue: float}>,
     *     sellers: list<array{name: string, count: int, total: float}>,
     *     shortages: array{count: int, items: list<array{label: string, stock: int, alert: int, ordered: int}>},
     *     awaiting: array{count: int, value: float},
     *     losses: array{count: int, value: float}
     * }
     */
    public function build(Carbon $date): array
    {
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $revenue = $this->saleService->revenueBetween($from, $to);
        $salesCount = Sale::whereBetween('created_at', [$from, $to])->count();
        // Même jour de la semaine précédente : comparaison à jour égal (le samedi avec le samedi)
        $previousDate = $date->copy()->subWeek();
        $previous = $this->saleService->revenueBetween($previousDate->copy()->startOfDay(), $previousDate->copy()->endOfDay());
        $margin = $this->saleService->getGrossMargin($from, $to);

        return [
            'date' => $date->copy()->startOfDay(),
            'revenue' => $revenue,
            'refunds' => round((float) $this->saleService->refundsQuery($from, $to)->sum('total'), 2),
            'sales_count' => $salesCount,
            'average' => $salesCount > 0 ? round((float) Sale::whereBetween('created_at', [$from, $to])->avg('total_net'), 2) : 0.0,
            'previous' => [
                'date' => $previousDate->startOfDay(),
                'revenue' => $previous,
                'change' => $previous > 0 ? round(($revenue - $previous) / $previous * 100, 1) : null,
            ],
            'margin' => ['margin' => $margin['margin'], 'rate' => $margin['rate'], 'excluded' => $margin['excluded']],
            'payments' => $this->payments($from, $to),
            'top_products' => $this->topProducts($from, $to),
            'sellers' => $this->sellers($from, $to),
            'shortages' => $this->shortages(),
            'awaiting' => array_intersect_key($this->purchaseService->getAwaitingDelivery(), ['count' => 0, 'value' => 0]),
            'losses' => $this->losses($from, $to),
        ];
    }

    /** Encaissements par mode de paiement, et par opérateur en Mobile Money. */
    private function payments(Carbon $from, Carbon $to): array
    {
        return Sale::whereBetween('created_at', [$from, $to])
            ->select('payment_method', 'payment_operator', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_net) as total'))
            ->groupBy('payment_method', 'payment_operator')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->payment_method,
                'operator' => $row->payment_operator,
                'count' => (int) $row->count,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /** Les produits qui ont fait le plus de chiffre dans la journée (remise de la vente répartie sur ses lignes). */
    private function topProducts(Carbon $from, Carbon $to, int $limit = 5): array
    {
        $rows = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$from, $to])
            ->groupBy('sale_items.product_color_id')
            ->select('sale_items.product_color_id')
            ->selectRaw('SUM(sale_items.quantity) as quantity')
            ->selectRaw('SUM(CASE WHEN sales.total_brut > 0 THEN sale_items.subtotal * sales.total_net / sales.total_brut ELSE 0 END) as revenue')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->with(['productColor.product', 'productColor.color', 'productColor.brand'])
            ->get();

        return $rows->map(fn ($row) => [
            'label' => $row->productColor?->label ?? '—',
            'quantity' => (int) $row->quantity,
            'revenue' => round((float) $row->revenue, 2),
        ])->all();
    }

    /** Ventes de chaque vendeur dans la journée. */
    private function sellers(Carbon $from, Carbon $to): array
    {
        return Sale::whereBetween('created_at', [$from, $to])
            ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_net) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->user->name ?? '—',
                'count' => (int) $row->count,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    /** Articles à réapprovisionner maintenant (commandes en cours comptées), les plus en manque d'abord. */
    private function shortages(int $limit = 5): array
    {
        $products = $this->purchaseService->getShortageProductsGroupedBySupplier()
            ->flatMap(fn (array $group) => $group['products'])
            ->sortBy(fn (object $product) => $product->stock + $product->ordered_quantity - $product->alert_stock)
            ->values();

        return [
            'count' => $products->count(),
            'items' => $products->take($limit)->map(fn (object $product) => [
                'label' => $product->label ?? $product->name ?? '—',
                'stock' => (int) $product->stock,
                'alert' => (int) $product->alert_stock,
                'ordered' => (int) $product->ordered_quantity,
            ])->all(),
        ];
    }

    /** Pertes non expliquées de la journée (corrections en sortie, écarts d'inventaire négatifs). */
    private function losses(Carbon $from, Carbon $to): array
    {
        $lines = collect($this->stockService->getUnexplainedLosses($from)['lines'])
            ->filter(fn (array $line) => $line['date'] <= $to);

        return ['count' => $lines->count(), 'value' => round((float) $lines->sum('value'), 2)];
    }

    // --- Envoi --------------------------------------------------------------------------------

    public function isEnabled(): bool
    {
        return filter_var($this->settings->get(self::ENABLED, false), FILTER_VALIDATE_BOOLEAN);
    }

    /** Heure d'envoi (0 à 23), 20 h par défaut. */
    public function hour(): int
    {
        $hour = $this->settings->get(self::HOUR);

        return is_numeric($hour) && $hour >= 0 && $hour <= 23 ? (int) $hour : self::DEFAULT_HOUR;
    }

    /** Adresses des réglages ; à défaut, celles des administrateurs actifs. */
    public function recipients(): array
    {
        $configured = self::parseRecipients((string) $this->settings->get(self::RECIPIENTS, ''));

        return $configured ?: User::where('role', 'back_office')->active()->pluck('email')->filter()->values()->all();
    }

    /** « a@x.mg, b@y.mg » ou une adresse par ligne → liste sans doublons. */
    public static function parseRecipients(string $value): array
    {
        return collect(preg_split('/[\s,;]+/', $value))->map(fn ($email) => trim($email))->filter()->unique()->values()->all();
    }

    /**
     * Envoie le résumé du jour $date à $recipients (destinataires des réglages par défaut).
     *
     * @return list<string> adresses servies
     *
     * @throws \Throwable si le serveur d'e-mail refuse l'envoi (message affiché tel quel)
     */
    public function send(Carbon $date, ?array $recipients = null): array
    {
        $recipients ??= $this->recipients();
        if ($recipients === []) {
            return [];
        }

        Mail::to($recipients)->send(new DailySummaryMail($this->build($date)));

        return $recipients;
    }

    /** Date du dernier envoi planifié, pour ne pas envoyer deux fois le même jour. */
    public function lastSentAt(): ?Carbon
    {
        $value = $this->settings->get(self::LAST_SENT);

        return $value ? rescue(fn () => Carbon::parse($value), null, false) : null;
    }

    public function markSent(Carbon $at): void
    {
        $this->settings->updateSettings([self::LAST_SENT => $at->toDateTimeString()]);
    }
}
