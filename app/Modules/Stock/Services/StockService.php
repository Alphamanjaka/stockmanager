<?php

namespace App\Modules\Stock\Services;

use App\Models\User;
use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Stock\Models\StockMovement;
use App\Modules\Stock\Notifications\LowStockAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Seul point d'écriture du stock : chaque variation crée un mouvement historisé.
 * Entrées : achat reçu ; sorties : vente ; inventaire physique (adjustTo) ;
 * correction manuelle (correct), en secours d'une erreur de saisie.
 */
class StockService
{
    /** Début du motif d'une correction manuelle, pour la distinguer dans l'historique. */
    public const CORRECTION_PREFIX = 'Correction : ';

    /** Début du motif des écarts d'inventaire (InventoryService : « Inventaire <référence> »). */
    public const INVENTORY_PREFIX = 'Inventaire ';

    public function getStockEvolutionForProductVariants(array $productColorIds): array
    {
        $evolutionData = [];

        foreach ($productColorIds as $productColorId) {
            $evolutionData[$productColorId] = $this->getStockEvolutionForVariant($productColorId);
        }

        return $evolutionData;
    }
    /**
     * Entrée de stock (achat reçu, correction) : le stock augmente et le mouvement est enregistré.
     */
    public function addStock(int $productColorId, int $quantity, string $reason)
    {
        DB::transaction(function () use ($productColorId, $quantity, $reason) {
            $variant = ProductColor::lockForUpdate()->findOrFail($productColorId);

            $before = $variant->stock;
            $after = $before + $quantity;
            $this->writeStock($variant, $after);

            $this->createStockMovement([
                'product_color_id' => $productColorId,
                'quantity' => $quantity,
                'type' => 'in',
                'reason' => $reason,
                'stock_before' => $before,
                'stock_after' => $after,
            ]);
        });
    }

    /**
     * Sortie de stock (vente, correction) : refusée si le stock est insuffisant.
     */
    public function removeStock(int $productColorId, int $quantity, string $reason)
    {
        DB::transaction(function () use ($productColorId, $quantity, $reason) {
            $variant = ProductColor::with('product', 'color')->lockForUpdate()->findOrFail($productColorId);

            if ($variant->stock < $quantity) {
                throw new \Exception("insufficient stock for {$variant->label}.");
            }

            $before = $variant->stock;
            $after = $before - $quantity;
            $this->writeStock($variant, $after);

            $this->createStockMovement([
                'product_color_id' => $productColorId,
                'quantity' => -$quantity, // Négatif pour une sortie
                'type' => 'out',
                'reason' => $reason,
                'stock_before' => $before,
                'stock_after' => $after,
            ]);
        });
    }
    /**
     * Correction manuelle, en secours d'une erreur de saisie (casse, erreur de comptage, retour...) :
     * le motif est obligatoire et préfixé par « Correction : ».
     *
     * @param  string  $type  'in' (ajout) ou 'out' (retrait)
     */
    public function correct(int $productColorId, string $type, int $quantity, string $reason): void
    {
        $reason = self::CORRECTION_PREFIX . trim($reason);

        if ($type === 'in') {
            $this->addStock($productColorId, $quantity, $reason);
        } else {
            $this->removeStock($productColorId, $quantity, $reason);
        }
    }

    /**
     * Ramène le stock d'une variante à une quantité cible (inventaire physique)
     * en enregistrant le mouvement correspondant à l'écart.
     *
     * @return StockMovement|null le mouvement créé, ou null si le stock était déjà à la cible
     */
    public function adjustTo(int $productColorId, int $target, string $reason): ?StockMovement
    {
        return DB::transaction(function () use ($productColorId, $target, $reason) {
            $variant = ProductColor::lockForUpdate()->findOrFail($productColorId);

            $target = max(0, $target);
            $before = $variant->stock;
            $delta = $target - $before;

            if ($delta === 0) {
                return null;
            }

            $this->writeStock($variant, $target);

            return $this->createStockMovement([
                'product_color_id' => $productColorId,
                'quantity' => $delta,
                'type' => $delta > 0 ? 'in' : 'out',
                'reason' => $reason,
                'stock_before' => $before,
                'stock_after' => $target,
            ]);
        });
    }

    /**
     * Seule écriture de la colonne stock de l'application, toujours accompagnée d'un mouvement.
     * Elle passe par une requête : le modèle ProductColor refuse qu'on modifie son stock directement.
     */
    private function writeStock(ProductColor $variant, int $stock): void
    {
        $before = (int) $variant->stock;

        ProductColor::whereKey($variant->id)->update(['stock' => $stock]);
        $variant->setRawAttributes(['stock' => $stock] + $variant->getAttributes(), true);

        $this->notifyAlertThreshold($variant, $before, $stock);
    }

    /**
     * Cloche du back office. Passage sous le seuil d'alerte (stock ≤ seuil) : notification aux administrateurs.
     * Retour au-dessus : les notifications non lues de la variante sont marquées comme lues.
     * Comme sur la page des ruptures, un seuil à 0 ou un produit archivé ne donne pas d'alerte.
     * Les notifications sont écrites dans la transaction du mouvement : annulées avec lui.
     */
    private function notifyAlertThreshold(ProductColor $variant, int $before, int $after): void
    {
        $alert = (int) $variant->alert_stock;
        if ($alert <= 0) {
            return;
        }

        if ($before > $alert && $after <= $alert) {
            $variant->loadMissing(['product', 'color', 'brand']);
            if ($variant->product->isArchived()) {
                return;
            }

            Notification::send(User::where('role', 'back_office')->active()->get(), new LowStockAlert($variant));
        } elseif ($before <= $alert && $after > $alert) {
            $ids = DatabaseNotification::where('type', LowStockAlert::class)->whereNull('read_at')->get()
                ->filter(fn (DatabaseNotification $notification) => ($notification->data['product_color_id'] ?? null) === $variant->id)
                ->modelKeys();

            DatabaseNotification::whereKey($ids)->update(['read_at' => now()]);
        }
    }

    public function getAllStockMovements(array $filters = [], int $perPage = 15)
    {
        return $this->stockMovementsQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Historique des mouvements (page et export) : recherche (motif, produit), type, période.
     * Les bornes de la période sont incluses (jour entier) et chacune est facultative.
     */
    public function stockMovementsQuery(array $filters = []): Builder
    {
        $query = StockMovement::with(['productColor.product', 'productColor.color', 'productColor.brand']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereLike('reason', "%{$filters['search']}%")
                    ->orWhereHas('productColor.product', function ($sq) use ($filters) {
                        $sq->whereLike('name', "%{$filters['search']}%");
                    });
            });
        }

        if (in_array($filters['type'] ?? null, ['in', 'out'], true)) {
            $query->where('type', $filters['type']);
        }

        foreach (['date_from' => '>=', 'date_to' => '<='] as $bound => $operator) {
            $value = $filters[$bound] ?? null;
            if ($value instanceof \DateTimeInterface || (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value))) {
                $query->whereDate('created_at', $operator, $value);
            }
        }

        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }
    public function getStockMovementById(int $id): StockMovement
    {
        return StockMovement::with(['productColor.product', 'productColor.color', 'productColor.brand'])->findOrFail($id);
    }

    public function getStockMovementsForProduct(int $productId, int $perPage = 10)
    {
        return StockMovement::whereHas('productColor', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })
            ->with(['productColor.color', 'productColor.product', 'productColor.brand'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get stock evolution data for a specific variant (ProductColor).
     */
    public function getStockEvolutionForVariant(int $productColorId): array
    {
        $variant = ProductColor::findOrFail($productColorId);
        $movements = StockMovement::where('product_color_id', $productColorId)
            ->orderBy('created_at', 'desc')
            ->get();

        $stockLevel = $variant->stock;
        $dataPoints = [];

        $dataPoints[] = ['x' => now()->toIso8601String(), 'y' => $stockLevel];

        foreach ($movements as $movement) {
            $stockLevel -= $movement->quantity;
            $dataPoints[] = ['x' => $movement->created_at->toIso8601String(), 'y' => $stockLevel];
        }

        return array_reverse($dataPoints);
    }

    /**
     * Get global stock evolution data for a specific product across all its variants.
     */
    public function getStockEvolutionForProduct(int $productId): array
    {
        $productColors = ProductColor::where('product_id', $productId)->get();
        $allEvolutionData = [];

        // Get evolution data for each variant
        foreach ($productColors as $productColor) {
            $allEvolutionData[$productColor->id] = $this->getStockEvolutionForVariant($productColor->id);
        }

        // Aggregate the data for global stock evolution
        $globalEvolution = [];
        $allDates = [];

        // Collect all unique dates from all variants' movements
        foreach ($allEvolutionData as $variantId => $evolution) {
            foreach ($evolution as $dataPoint) {
                $allDates[] = $dataPoint['x'];
            }
        }
        $allDates = array_unique($allDates);
        sort($allDates); // Ensure dates are in chronological order

        // For each unique date, calculate the total stock across all variants
        foreach ($allDates as $date) {
            $totalStockAtDate = 0;
            foreach ($productColors as $productColor) {
                $variantId = $productColor->id;
                $variantEvolution = $allEvolutionData[$variantId];

                // Find the stock level for this variant at or before the current date
                $stockForVariant = 0;
                foreach ($variantEvolution as $dataPoint) {
                    if ($dataPoint['x'] <= $date) {
                        $stockForVariant = $dataPoint['y'];
                    }
                }
                $totalStockAtDate += $stockForVariant;
            }
            $globalEvolution[] = ['x' => $date, 'y' => $totalStockAtDate];
        }

        return $globalEvolution;
    }
    public function createStockMovement(array $data): StockMovement
    {
        return StockMovement::create($data);
    }

    /**
     * Récupère les variantes "dormantes" (stock > 0 mais aucune sortie depuis X jours).
     */
    public function getDormantProducts(int $days = 60)
    {
        $cutoffDate = now()->subDays($days);

        return ProductColor::with(['product', 'color', 'brand'])
            ->where('stock', '>', 0)
            ->whereNotExists(function ($query) use ($cutoffDate) {
                $query->select(DB::raw(1))
                    ->from('stock_movements')
                    ->whereColumn('stock_movements.product_color_id', 'product_colors.id')
                    ->where('type', 'out')
                    ->where('created_at', '>=', $cutoffDate);
            })
            ->limit(20)
            ->get();
    }

    public function getRotationStats(int $limit = 5)
    {
        return StockMovement::select('product_color_id', DB::raw('SUM(ABS(quantity)) as total_out'))
            ->where('type', 'out')
            ->groupBy('product_color_id')
            ->orderByDesc('total_out')
            ->with(['productColor.product', 'productColor.color', 'productColor.brand'])
            ->limit($limit)
            ->get();
    }

    /**
     * Calcule l'évolution de la valeur totale du stock sur les X derniers jours.
     * La valeur est calculée au prix de vente de chaque variante, aussi bien pour la valeur actuelle
     * que pour la reconstitution des jours précédents (le prix du produit parent n'est plus utilisé).
     */
    public function getTotalStockValueEvolution(int $days = 30): array
    {
        $currentTotalValue = ProductColor::sum(DB::raw('stock * price'));

        $movements = StockMovement::with('productColor')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->orderBy('created_at', 'desc')
            ->get();

        $movementsByDay = $movements->groupBy(function ($m) {
            return $m->created_at->format('Y-m-d');
        });

        $dataPoints = [];
        $runningValue = $currentTotalValue;

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i);
            $dateString = $date->format('Y-m-d');

            $dataPoints[] = [
                'date' => $date->format('d/m'),
                'value' => max(0, round($runningValue, 2))
            ];

            if (isset($movementsByDay[$dateString])) {
                foreach ($movementsByDay[$dateString] as $movement) {
                    if ($movement->productColor) {
                        $runningValue -= ($movement->quantity * $movement->productColor->price);
                    }
                }
            }
        }

        return array_reverse($dataPoints);
    }

    /**
     * Pertes non expliquées depuis une date : les sorties qui ne viennent ni d'une vente ni de l'annulation
     * d'un achat, c'est-à-dire les corrections en sortie et les écarts d'inventaire négatifs.
     * Valorisées au coût d'achat de la variante, ou à son prix de vente quand le coût n'est pas renseigné.
     *
     * @return array{lines: list<array>, value: float, stock_value: float, percentage: float}
     *         percentage : part des pertes dans la valeur du stock avant ces pertes
     */
    public function getUnexplainedLosses(\DateTimeInterface $since): array
    {
        $lines = StockMovement::with(['productColor.product', 'productColor.color', 'productColor.brand'])
            ->where('type', 'out')
            ->where('created_at', '>=', $since)
            ->where(fn ($q) => $q->where('reason', 'like', self::CORRECTION_PREFIX . '%')
                ->orWhere('reason', 'like', self::INVENTORY_PREFIX . '%'))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (StockMovement $movement) => [
                'date' => $movement->created_at,
                'label' => $movement->productColor->label,
                'sku' => $movement->productColor->sku,
                'quantity' => abs($movement->quantity),
                'reason' => $movement->reason,
                'value' => abs($movement->quantity) * $this->unitValue($movement->productColor),
            ]);

        $value = (float) $lines->sum('value');
        $stockValue = (float) ProductColor::sum(DB::raw('stock * COALESCE(NULLIF(price_purchase, 0), price)'));
        $valueBefore = $stockValue + $value;

        return [
            'lines' => $lines->all(),
            'value' => round($value, 2),
            'stock_value' => round($stockValue, 2),
            'percentage' => $valueBefore > 0 ? round($value / $valueBefore * 100, 2) : 0.0,
        ];
    }

    /** Valeur d'une unité perdue : coût d'achat, ou prix de vente à défaut. */
    private function unitValue(ProductColor $variant): float
    {
        return (float) $variant->price_purchase > 0 ? (float) $variant->price_purchase : (float) $variant->price;
    }

    /**
     * Recherche les mouvements de stock pour la recherche globale.
     */
    public function searchForGlobalSearch(string $query, int $limit = 5): \Illuminate\Support\Collection
    {
        return StockMovement::with(['productColor.product'])
            ->whereLike('reason', "%{$query}%")
            ->orWhereHas('productColor.product', fn($q) => $q->whereLike('name', "%{$query}%"))
            ->limit($limit)
            ->get()
            ->map(fn(StockMovement $movement) => [
                'type' => 'movement',
                'id' => $movement->id,
                'name' => "Mouv. " . strtoupper($movement->type) . ": " . ($movement->reason ?? 'Sans raison'),
                'url' => route('admin.movements.show', $movement->id),
            ]);
    }
}
