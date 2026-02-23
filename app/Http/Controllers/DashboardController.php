<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        // Redirection automatique vers le bon dashboard selon le rôle
        $user = auth()->user();

        if ($user->isBackOffice()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isFrontOffice()) {
            return redirect()->route('sales.dashboard');
        }

        // Fallback si aucun rôle n'est détecté (sécurité)
        return view('dashboard', [
            'user' => $user,
            'canAccessBackOffice' => $user->isBackOffice(),
            'canAccessFrontOffice' => $user->isFrontOffice(),
        ]);
    }

    /**
     * Display back office dashboard (products management)
     */
    public function backOffice(Request $request)
    {
        if (!auth()->user()->isBackOffice()) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        $period = $request->get('period', '7days');
        $data = $this->dashboardService->getBackOfficeData($period);

        // Les données du graphique ('labels', 'values') sont maintenant chargées via API.
        return view('back-office.dashboard', [
            'salesToday' => $data['salesToday'],
            'salesThisMonth' => $data['salesThisMonth'],
            'totalProducts' => $data['totalProducts'],
            'lowStockProducts' => $data['lowStockProducts'],
            'totalCategories' => $data['totalCategories'],
            'totalSuppliers' => $data['totalSuppliers'],
            'totalSales' => $data['totalSales'],
            'mostSoldProduct' => $data['mostSoldProduct'],
            'leastSoldProduct' => $data['leastSoldProduct'],
            'period' => $data['period'],
        ]);
    }

    /**
     * Display front office dashboard (sales management)
     */
    public function frontOffice(Request $request)
    {
        if (!auth()->user()->isFrontOffice()) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        $data = $this->dashboardService->getFrontOfficeData();

        return view('front-office.dashboard', [
            'user' => auth()->user(),
            'salesToday' => $data['salesToday'],
            'salesThisMonth' => $data['salesThisMonth'],
            'totalSales' => $data['totalSales'],
        ]);
    }

    /**
     * Fournit les données du graphique des ventes pour le back-office via une API.
     */
    public function getChartDataApi(Request $request)
    {
        if (!auth()->user()->isBackOffice()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'period' => 'in:7days,1month,1year'
        ]);

        $period = $request->get('period', '7days');
        $chartData = $this->dashboardService->getChartData($period);

        return response()->json($chartData);
    }
}
