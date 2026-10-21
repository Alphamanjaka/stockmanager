<?php

namespace App\Modules\Sale\Http\Controllers\Concerns;

use App\Modules\Sale\Http\Requests\StoreSaleRequest;
use App\Modules\Sale\Models\Sale;

/**
 * Enregistrement d'une vente, commun au front office (caisse vendeur) et au back office.
 *
 * L'écran de caisse envoie la vente en AJAX et reste ouvert : il reçoit la monnaie à rendre,
 * l'adresse du reçu à imprimer et les stocks à jour. Un formulaire classique est redirigé vers la fiche vente.
 *
 * Le contrôleur doit exposer $this->saleService.
 */
trait ChecksOutSales
{
    /**
     * @param  string  $routePrefix  préfixe des routes de l'espace : 'saler.' ou 'admin.sales.'
     */
    protected function checkout(StoreSaleRequest $request, string $routePrefix)
    {
        try {
            $validated = $request->validated();

            $sale = $this->saleService->createSale(
                $validated['products'],
                $validated['discount'] ?? 0,
                auth()->id(), // Associer la vente à l'utilisateur connecté
                $validated
            );
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la vente : '.$e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json($this->checkoutSummary($sale, $routePrefix), 201);
        }

        return redirect()
            ->route("{$routePrefix}show", $sale->id)
            ->with('success', "Vente {$sale->reference} validée avec succès !");
    }

    /** Ce que la caisse affiche après l'encaissement. */
    private function checkoutSummary(Sale $sale, string $routePrefix): array
    {
        $sale->load('items.productColor');

        return [
            'id' => $sale->id,
            'reference' => $sale->reference,
            'total_net' => (float) $sale->total_net,
            'payment_method' => $sale->payment_method,
            'amount_received' => (float) $sale->amount_received,
            'change_due' => $sale->change_due,
            'receipt_url' => route("{$routePrefix}receipt", $sale->id),
            'show_url' => route("{$routePrefix}show", $sale->id),
            // Stock restant des articles vendus, pour que la caisse reste à jour sans recharger la page
            'stocks' => $sale->items->mapWithKeys(fn ($item) => [$item->product_color_id => (int) $item->productColor->stock]),
        ];
    }
}
