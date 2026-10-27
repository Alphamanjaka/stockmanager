<?php

namespace App\Core\Http\Controllers;

use App\Core\Services\DashboardService;
use App\Core\Support\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        // Redirection automatique vers le bon dashboard selon le rôle
        $user = auth()->user();

        // Première page du rôle : tableau de bord, ou les achats à réceptionner pour un magasinier
        if ($user->isBackOffice() || $user->isFrontOffice()) {
            return redirect()->route(Role::landingRoute($user->role));
        }

        // Fallback si aucun rôle n'est détecté (sécurité)
        return view('core::dashboard', [
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
        if (! auth()->user()->isBackOffice()) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        // Les données du graphique ('labels', 'values') sont chargées via API (chartData).
        return view('core::back-office.dashboard', $this->dashboardService->getBackOfficeData($request->get('period', '7days')));
    }

    /**
     * Display front office dashboard (sales management)
     */
    public function frontOffice(Request $request)
    {
        if (! auth()->user()->isFrontOffice()) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        $data = $this->dashboardService->getFrontOfficeData(auth()->id());

        return view('core::front-office.dashboard', [
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
        if (! auth()->user()->isBackOffice()) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'period' => 'in:7days,1month,1year',
        ]);

        $period = $request->get('period', '7days');
        $chartData = $this->dashboardService->getChartData($period);

        return response()->json($chartData);
    }
}
