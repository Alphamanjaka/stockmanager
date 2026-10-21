<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\ProductColor;
use App\Modules\Inventory\Models\Inventory;
use App\Modules\Stock\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryService
{
    public function __construct(protected StockService $stockService) {}

    public function getAll(int $perPage = 15)
    {
        return $this->listQuery()->paginate($perPage);
    }

    /** Liste des inventaires (page et export), du plus récent au plus ancien. */
    public function listQuery(): Builder
    {
        return Inventory::with(['category', 'user', 'validator'])
            ->withCount([
                'lines',
                'lines as counted_lines_count' => fn ($q) => $q->whereNotNull('counted_quantity'),
            ])
            ->latest()
            ->latest('id');
    }

    /**
     * Ouvre un inventaire : une ligne par variante (toutes, ou celles d'une catégorie),
     * avec le stock actuel comme quantité attendue.
     */
    public function create(?int $categoryId, ?string $notes, ?int $userId): Inventory
    {
        return DB::transaction(function () use ($categoryId, $notes, $userId) {
            $inventory = Inventory::create([
                'reference' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'status' => Inventory::DRAFT,
                'category_id' => $categoryId,
                'notes' => $notes,
                'user_id' => $userId,
            ]);

            // Produits archivés : comptés seulement s'il en reste en stock (ils sont encore en rayon)
            $variants = ProductColor::query()
                ->when($categoryId, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $categoryId)))
                ->where(fn ($q) => $q->whereHas('product', fn ($p) => $p->active())->orWhere('stock', '!=', 0))
                ->get(['id', 'stock']);

            // Insertion par lots : PostgreSQL limite le nombre de paramètres par requête
            $now = now();
            foreach ($variants->chunk(1000) as $chunk) {
                $inventory->lines()->insert($chunk->map(fn (ProductColor $variant) => [
                    'inventory_id' => $inventory->id,
                    'product_color_id' => $variant->id,
                    'expected_quantity' => $variant->stock,
                    'counted_quantity' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all());
            }

            return $inventory;
        });
    }

    public function getById(int $id): Inventory
    {
        return Inventory::with(['category', 'user', 'validator'])->findOrFail($id);
    }

    /** Lignes de l'inventaire avec produit, couleur et stock actuel, triées par produit. */
    public function linesFor(Inventory $inventory)
    {
        return $inventory->lines()
            ->with(['productColor.product', 'productColor.color', 'productColor.brand'])
            ->get()
            ->sortBy(fn ($line) => mb_strtolower($line->productColor->label ?? ''))
            ->values();
    }

    /**
     * Enregistre les quantités comptées ([id de ligne => quantité], vide = non comptée).
     * Une ligne dont le comptage change garde le stock de la variante à cet instant (stock_at_count) :
     * l'écart sera mesuré par rapport à lui. Une ligne inchangée garde le stock relevé à son comptage.
     *
     * @throws \Exception si l'inventaire est déjà validé
     */
    public function saveCounts(Inventory $inventory, array $counts): void
    {
        DB::transaction(function () use ($inventory, $counts) {
            $locked = Inventory::lockForUpdate()->findOrFail($inventory->id);
            if (!$locked->isDraft()) {
                throw new \Exception("L'inventaire {$locked->reference} est validé : les comptages ne peuvent plus être modifiés.");
            }

            $lines = $locked->lines()->whereIn('id', array_keys($counts))->get();
            $stocks = ProductColor::whereIn('id', $lines->pluck('product_color_id'))->pluck('stock', 'id');

            foreach ($lines as $line) {
                $value = $counts[$line->id];
                $counted = ($value === null || $value === '') ? null : max(0, (int) $value);

                if ($counted === $line->counted_quantity) {
                    continue;
                }

                $line->update([
                    'counted_quantity' => $counted,
                    'stock_at_count' => $counted === null ? null : (int) $stocks[$line->product_color_id],
                ]);
            }
        });
    }

    /**
     * Valide l'inventaire : l'écart de chaque ligne comptée (compté − stock au comptage) est appliqué
     * au stock courant, avec un mouvement de stock « Inventaire <référence> ». Les ventes et réceptions
     * faites depuis le comptage sont donc conservées. Les lignes non comptées sont ignorées.
     *
     * @throws \Exception si l'inventaire est déjà validé
     */
    public function validate(Inventory $inventory, ?int $userId): Inventory
    {
        return DB::transaction(function () use ($inventory, $userId) {
            // Verrou : deux validations simultanées ne peuvent pas ajuster deux fois le stock
            $locked = Inventory::lockForUpdate()->findOrFail($inventory->id);
            if (!$locked->isDraft()) {
                throw new \Exception("L'inventaire {$locked->reference} a déjà été validé.");
            }

            $lines = $locked->lines()->whereNotNull('counted_quantity')->get();
            foreach ($lines as $line) {
                // Stock lu sous verrou : une vente simultanée attend la fin de la validation
                $stock = ProductColor::lockForUpdate()->whereKey($line->product_color_id)->value('stock');
                // Ligne comptée avant l'enregistrement du stock au comptage : le stock actuel sert de référence
                $atCount = $line->stock_at_count ?? $stock;

                $this->stockService->adjustTo($line->product_color_id, $stock + $line->counted_quantity - $atCount, "Inventaire {$locked->reference}");
                $line->update(['stock_at_count' => $atCount, 'stock_at_validation' => $stock]);
            }

            $locked->update([
                'status' => Inventory::VALIDATED,
                'validated_by' => $userId,
                'validated_at' => now(),
            ]);

            return $locked;
        });
    }

    /**
     * @throws \Exception si l'inventaire est validé (il fait partie de l'historique du stock)
     */
    public function delete(Inventory $inventory): void
    {
        if (!$inventory->isDraft()) {
            throw new \Exception("Un inventaire validé ne peut pas être supprimé.");
        }

        $inventory->delete();
    }
}
