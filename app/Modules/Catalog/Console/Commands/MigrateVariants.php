<?php

namespace App\Modules\Catalog\Console\Commands;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Support\VariantOptions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Variantes flexibles, étape 2 : remplit les nouvelles colonnes à partir des données existantes.
 *
 * - Couleur « Rouge »               → option_values {"Couleur":"Rouge"}
 * - Couleur « No Variant / Sans variante » → aucune option (variante par défaut)
 * - signature et SKU générés, options du produit déduites de ses variantes
 *
 * Sans risque : color_id n'est pas modifié, rien n'est supprimé, les dates de modification sont conservées,
 * et la commande peut être relancée (seul ce qui manque est complété).
 */
class MigrateVariants extends Command
{
    protected $signature = 'catalog:migrate-variants
                            {--dry-run : Affiche le rapport sans rien écrire}';

    protected $description = 'Remplit les options, signatures et SKU des variantes à partir des couleurs existantes';

    private array $stats = [];

    private array $reservedSkus = [];

    private array $duplicates = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->stats = ['products' => 0, 'product_options' => 0, 'variants' => 0, 'with_color' => 0, 'default' => 0, 'already_done' => 0];

        $this->info($dryRun ? 'Simulation (--dry-run) : aucune donnée ne sera modifiée.' : 'Migration des variantes…');

        Product::query()
            ->with(['productColors.color', 'productColors.brand'])
            ->chunkById(100, function ($products) use ($dryRun) {
                foreach ($products as $product) {
                    $dryRun ? $this->migrateProduct($product, false) : DB::transaction(fn () => $this->migrateProduct($product, true));
                }
            });

        $this->report($dryRun);

        return self::SUCCESS;
    }

    private function migrateProduct(Product $product, bool $write): void
    {
        $this->stats['products']++;
        $combinations = [];
        $signatures = [];

        foreach ($product->productColors as $variant) {
            if ($variant->option_values !== null && $variant->signature !== null && $variant->sku) {
                $this->stats['already_done']++;
                $values = $variant->option_values;
                $signature = $variant->signature;
            } else {
                $values = $variant->option_values ?? VariantOptions::fromColor($variant->color);
                $signature = VariantOptions::signature($values, $variant->brand_id);
                $sku = $variant->sku ?: VariantOptions::generateSku($product->name, $values, $variant->brand?->name, $variant->id, $this->reservedSkus, $product->options);
                $this->reservedSkus[] = $sku;

                $this->stats['variants']++;
                $values === [] ? $this->stats['default']++ : $this->stats['with_color']++;

                if ($write) {
                    // Écriture brute : pas d'événement, dates de modification inchangées
                    $variant->timestamps = false;
                    $variant->forceFill(['option_values' => $values, 'signature' => $signature, 'sku' => $sku])->saveQuietly();
                }
            }

            $combinations[] = $values;
            $signatures[$signature][] = $variant;
        }

        foreach ($signatures as $signature => $variants) {
            if (count($variants) > 1) {
                $this->duplicates[] = [
                    $product->id,
                    $product->name,
                    $signature === '' ? '(sans option)' : $signature,
                    collect($variants)->pluck('id')->implode(', '),
                ];
            }
        }

        if ($product->options === null) {
            $this->stats['product_options']++;
            if ($write) {
                $product->timestamps = false;
                $product->forceFill(['options' => VariantOptions::optionsFromVariants($combinations)])->saveQuietly();
            }
        }
    }

    private function report(bool $dryRun): void
    {
        $verb = $dryRun ? 'à migrer' : 'migrées';

        $this->table(['Élément', 'Nombre'], [
            ['Produits examinés', $this->stats['products']],
            ["Produits dont les options sont {$verb}", $this->stats['product_options']],
            ["Variantes {$verb}", $this->stats['variants']],
            ['  dont avec une couleur → option « Couleur »', $this->stats['with_color']],
            ['  dont « sans variante » → variante par défaut', $this->stats['default']],
            ['Variantes déjà migrées (ignorées)', $this->stats['already_done']],
            ['Doublons détectés', count($this->duplicates)],
        ]);

        if ($this->duplicates) {
            $this->warn('Doublons : plusieurs variantes d\'un même produit ont la même combinaison.');
            $this->warn('Ils n\'empêchent pas la migration, mais devront être fusionnés avant l\'étape 4 (unicité).');
            $this->table(['Produit #', 'Nom', 'Combinaison', 'Variantes #'], $this->duplicates);
        }
    }
}
