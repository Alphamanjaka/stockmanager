<?php

namespace App\Core\Services;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Purchase\Services\PurchaseService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleReturn;
use App\Modules\Sale\Services\SaleService;
use App\Modules\Stock\Services\StockService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected SaleService $saleService,
        protected PurchaseService $purchaseService,
        protected StockService $stockService,
    ) {}

    /**
     * Tableau de bord du back office, orienté action : ce qui demande une décision aujourd'hui
     * (commandes à recevoir, ruptures à réapprovisionner, stock dormant), les ventes du jour et la marge,
     * puis les ventes de la semaine et du mois. Le graphique est chargé à part (getChartData).
     */
    public function getBackOfficeData($period = '7days')
    {
        return [
            'salesToday' => $this->getSalesToday(),
            'salesTodayCount' => Sale::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'marginThisMonth' => $this->saleService->getGrossMargin(now()->startOfMonth(), now()->endOfMonth()),
            'awaitingDelivery' => $this->purchaseService->getAwaitingDelivery(),
            'shortages' => $this->getShortages(),
            'dormantStock' => $this->stockService->getDormantStock(),
            'salesThisWeek' => $this->getSalesThisWeek(),
            'salesThisMonth' => $this->getSalesThisMonth(),
            'mostSoldProduct' => $this->getMostSoldProduct(),
            'period' => $period,
        ];
    }

    /**
     * Variantes à réapprovisionner : au seuil d'alerte ou en dessous une fois les commandes en cours comptées
     * (même calcul que la page « Réapprovisionner les ruptures »), les plus en manque d'abord.
     *
     * @return array{count: int, below_threshold: int, items: Collection<int, object>}
     *                                                                                 below_threshold : variantes sous leur seuil, commandes en cours ou non
     */
    private function getShortages(int $limit = 5): array
    {
        $products = $this->purchaseService->getShortageProductsGroupedBySupplier()
            ->flatMap(fn (array $group) => $group['products'])
            ->sortBy(fn (object $product) => $product->stock + $product->ordered_quantity - $product->alert_stock)
            ->values();

        return [
            'count' => $products->count(),
            'below_threshold' => $this->getLowStockProducts(),
            'items' => $products->take($limit),
        ];
    }

    /**
     * Get sales sum for a specific range
     */
    /**
     * Chiffre d'affaires de la période, avoirs déduits (un avoir compte le jour où il est fait).
     * $userId : limite aux ventes d'un vendeur (tableau de bord du front office).
     */
    private function getSalesSumBetween($start, $end, ?int $userId = null)
    {
        return $this->saleService->revenueBetween($start, $end, $userId);
    }

    /**
     * Avoirs de la période, par jour (ou par mois avec $groupExpression) : [clé => montant remboursé].
     * Retirés des ventes de chaque point des graphiques.
     */
    private function refundsGroupedBy(string $groupExpression, $start, $end)
    {
        return SaleReturn::select(DB::raw("$groupExpression as period"), DB::raw('SUM(total) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->pluck('total', 'period');
    }

    private function getSalesToday(?int $userId = null)
    {
        return $this->getSalesSumBetween(now()->startOfDay(), now()->endOfDay(), $userId);
    }

    /** Semaine en cours, du lundi au dimanche (comme le graphique des 7 derniers jours). */
    private function getSalesThisWeek(?int $userId = null)
    {
        return $this->getSalesSumBetween(now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY), $userId);
    }

    private function getSalesThisMonth(?int $userId = null)
    {
        return $this->getSalesSumBetween(now()->startOfMonth(), now()->endOfMonth(), $userId);
    }

    private function getMostSoldProduct()
    {
        return $this->saleService->getMostSoldProduct();
    }

    /**
     * Get chart data for specified period
     */

    /**
     * Get chart data for specified period
     */
    public function getChartData($period)
    {
        // Jours et mois dans la langue de l'application (Carbon suit App::setLocale)
        return match ($period) {
            '1month' => $this->getChartDataByWeek(),
            '1year' => $this->getChartDataByMonth(),
            '7days' => $this->getChartDataByDay(),
            default => $this->getChartDataByDay(),
        };
    }

    /**
     * Get chart data for 7 days
     */
    private function getChartDataByDay()
    {
        $startOfWeek = now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = now()->endOfWeek(Carbon::SUNDAY);

        $salesData = Sale::select(
            DB::raw('DATE(created_at) as sale_date'),
            DB::raw('SUM(total_net) as total')
        )
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');
        $refunds = $this->refundsGroupedBy('DATE(created_at)', $startOfWeek, $endOfWeek);

        $labels = [];
        $values = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $dateKey = $day->toDateString();

            $labels[] = ucfirst($day->isoFormat('dddd'));
            $values[] = (float) $salesData->get($dateKey, 0) - (float) $refunds->get($dateKey, 0);
        }

        return compact('labels', 'values');
    }

    /**
     * Get chart data for 4 weeks
     */
    private function getChartDataByWeek()
    {
        $startDate = now()->subWeeks(3)->startOfWeek(Carbon::MONDAY);
        $endDate = now()->endOfWeek(Carbon::SUNDAY);

        $salesDataByDay = Sale::select(
            DB::raw('DATE(created_at) as sale_date'),
            DB::raw('SUM(total_net) as total')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');
        $refunds = $this->refundsGroupedBy('DATE(created_at)', $startDate, $endDate);

        $labels = [];
        $salesByWeek = [];

        for ($i = 3; $i >= 0; $i--) {
            $startOfWeek = now()->subWeeks($i)->startOfWeek(Carbon::MONDAY);
            $weekKey = $startOfWeek->toDateString();
            $labels[] = __('Week of :date', ['date' => $startOfWeek->format('d/m')]);
            $salesByWeek[$weekKey] = 0;
        }

        foreach ($salesDataByDay as $date => $total) {
            $weekKey = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString();
            if (isset($salesByWeek[$weekKey])) {
                $salesByWeek[$weekKey] += $total;
            }
        }
        foreach ($refunds as $date => $total) {
            $weekKey = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString();
            if (isset($salesByWeek[$weekKey])) {
                $salesByWeek[$weekKey] -= $total;
            }
        }

        $values = array_values($salesByWeek);

        return compact('labels', 'values');
    }

    /**
     * Get chart data for 12 months
     */
    private function getChartDataByMonth()
    {
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now()->endOfMonth();

        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "TO_CHAR(created_at, 'YYYY-MM')",
            'sqlsrv' => "FORMAT(created_at, 'yyyy-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')", // default for mysql/mariadb
        };

        $salesData = Sale::select(
            DB::raw("$monthExpression as sale_month"),
            DB::raw('SUM(total_net) as total')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('sale_month')
            ->pluck('total', 'sale_month');
        $refunds = $this->refundsGroupedBy($monthExpression, $startDate, $endDate);

        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            $labels[] = ucfirst($date->isoFormat('MMM YYYY'));
            $values[] = (float) $salesData->get($monthKey, 0) - (float) $refunds->get($monthKey, 0);
        }

        return compact('labels', 'values');
    }

    /**
     * Get front office data
     */
    /** Chiffres du vendeur connecté uniquement : un vendeur ne voit que ses propres ventes. */
    public function getFrontOfficeData(int $userId)
    {
        return [
            'salesToday' => $this->getSalesToday($userId),
            'salesThisMonth' => $this->getSalesThisMonth($userId),
            'totalSales' => Sale::where('user_id', $userId)->count(),
        ];
    }

    /**
     * Variantes à leur seuil d'alerte ou en dessous. Sans seuil (0), une variante n'est pas surveillée,
     * comme pour les ruptures ; un produit archivé n'est plus réapprovisionné.
     */
    private function getLowStockProducts()
    {
        return ProductColor::whereColumn('stock', '<=', 'alert_stock')
            ->where('alert_stock', '>', 0)
            ->whereHas('product', fn ($query) => $query->active())
            ->count();
    }
}
