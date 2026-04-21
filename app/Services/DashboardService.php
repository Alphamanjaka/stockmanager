<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\ProductColor;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            'salesToday' => $this->getSalesToday(),
            'salesThisMonth' => $this->getSalesThisMonth(),
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
    private function getSalesSumBetween($start, $end)
    {
        return Sale::whereBetween('created_at', [$start, $end])->sum('total_net');
    }

    private function getSalesToday()
    {
        return $this->getSalesSumBetween(now()->startOfDay(), now()->endOfDay());
    }

    private function getSalesThisMonth()
    {
        return $this->getSalesSumBetween(now()->startOfMonth(), now()->endOfMonth());
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

        $labels = [];
        $values = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $dateKey = $day->toDateString();

            $labels[] = ucfirst($day->isoFormat('dddd'));
            $values[] = $salesData->get($dateKey, 0);
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

        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');

            $labels[] = ucfirst($date->isoFormat('MMM YYYY'));
            $values[] = $salesData->get($monthKey, 0);
        }

        return compact('labels', 'values');
    }

    /**
     * Get front office data
     */
    public function getFrontOfficeData()
    {
        return [
            'salesToday' => $this->getSalesToday(),
            'salesThisMonth' => $this->getSalesThisMonth(),
            'totalSales' => $this->getTotalSales(),
        ];
    }

    /**
     * Get total count of products
     */
    private function getTotalProducts()
    {
        return ProductColor::count();
    }

    /**
     * Get count of low stock variants (from product_colors table)
     */
    private function getLowStockProducts()
    {
        return ProductColor::whereColumn('stock', '<=', 'alert_stock')->count();
    }

    private function getTotalCategories() { return Category::count(); }
    private function getTotalSuppliers() { return Supplier::count(); }
    private function getTotalSales() { return Sale::count(); }

}
