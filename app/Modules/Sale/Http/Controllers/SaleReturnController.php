<?php

namespace App\Modules\Sale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sale\Http\Requests\StoreSaleReturnRequest;
use App\Modules\Sale\Models\SaleReturn;
use App\Modules\Sale\Services\SaleReturnService;
use App\Modules\Sale\Services\SaleService;

/**
 * Avoirs (back office seulement) : retour d'une partie des articles, ou annulation de la vente.
 */
class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleService $saleService,
        protected SaleReturnService $saleReturnService,
    ) {}

    public function create(int $sale)
    {
        $sale = $this->saleService->getSaleById($sale);

        if ($sale->returnStatus() === 'returned') {
            return redirect()->route('admin.sales.show', $sale->id)
                ->with('error', "La vente {$sale->reference} est déjà entièrement retournée.");
        }

        return view('sale::back-office.returns.create', [
            'sale' => $sale,
            'returned' => $sale->returnedQuantities(),
        ]);
    }

    public function store(StoreSaleReturnRequest $request, int $sale)
    {
        $reason = $request->validated('reason');
        $refundMethod = $request->validated('refund_method');

        try {
            $saleReturn = $request->boolean('all')
                ? $this->saleReturnService->cancel($sale, $reason, $refundMethod)
                : $this->saleReturnService->create($sale, $request->validated('quantities', []), $reason, $refundMethod);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $count = $saleReturn->items->sum('quantity');

        return redirect()->route('admin.sales.show', $sale)
            ->with('success', "Avoir {$saleReturn->reference} enregistré : {$count} article(s) remis en stock, "
                . money($saleReturn->total) . ' remboursé(s).');
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['sale.customer', 'user', 'items.productColor.product', 'items.productColor.color', 'items.productColor.brand']);

        return view('sale::back-office.returns.show', compact('saleReturn'));
    }
}
