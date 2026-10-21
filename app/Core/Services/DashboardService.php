<?php

namespace App\Core\Services;

use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleReturn;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Catalog\Models\Category;
use App\Modules\Contact\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Modules\Sale\Services\SaleService;

class DashboardService
{
    protected SaleService $saleService;
    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }
    /**
     * Get back office KPIs and chart data
     */
    public function getBackOfficeData($period = '7days')
    {
        return [
            'salesThisWeek' => $this->getSalesThisWeek(),
            'salesThisMonth' => $this->getSalesThisMonth(),
            'marginThisMonth' => $this->saleService->getGrossMargin(now()->startOfMonth(), now()->endOfMonth()),
            'totalProducts' => $this->getTotalProducts(),
            'lowStockProducts' => $this->getLowStockProducts(),
            'totalCategories' => $this->getTotalCategories(),
            'totalSuppliers' => $this->getTotalSuppliers(),
            'totalSales' => $this->getTotalSales(),
            'mostSoldProduct' => $this->getMostSoldProduct(),
            'leastSoldProduct' => $this->getLeastSoldProduct(),
            'chartData' => $this->getChartData($period),
            'period' => $period,
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
    private function getLeastSoldProduct()
    {
        return $this->saleService->getLeastSoldProduct();
    }

    /**
     * Get chart data for specified period
     */




    /**
     * Get chart data for specified period
     */
    public function getChartData($period)
    {
        Carbon::setLocale('fr');

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
            $labels[] = "Semaine du " . $startOfWeek->format('d/m');
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
     * Get total count of products (variantes des produits en service)
     */
    private function getTotalProducts()
    {
        return ProductColor::whereHas('product', fn ($query) => $query->active())->count();
    }

    /**
     * Get count of low stock variants (from product_colors table) ; un produit archivé n'est plus réapprovisionné
     */
    private function getLowStockProducts()
    {
        return ProductColor::whereColumn('stock', '<=', 'alert_stock')
            ->whereHas('product', fn ($query) => $query->active())
            ->count();
    }

    private function getTotalCategories() { return Category::active()->count(); }
    private function getTotalSuppliers() { return Supplier::active()->count(); }
    private function getTotalSales() { return Sale::count(); }

}