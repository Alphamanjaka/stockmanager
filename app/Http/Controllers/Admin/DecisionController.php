<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LlmLocalService;
use Illuminate\Http\Request;

class DecisionController extends Controller
{
    protected $llmService;

    // Injection automatique du service IA
    public function __construct(LlmLocalService $llmService)
    {
        $this->llmService = $llmService;
    }

    public function analyserStockFaible()
    {
        // Données fictives extraites de votre base de données Laragon
        $contexte = "Analyse de stock pour réapprovisionnement e-commerce";
        $donnees = "Produit: Machine à café Expresso. Stock actuel: 2 unités. Ventes les 7 derniers jours: 15 unités. Délai de livraison fournisseur: 5 jours.";

        // Appel de l'IA locale (LM Studio)
        $recommandation = $this->llmService->obtenirAideDecision($contexte, $donnees);

        // Envoyer le résultat à votre vue Blade
        return view('admin.decision', compact('recommandation'));
    }
}