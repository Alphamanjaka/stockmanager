<?php

namespace App\Imports;

use App\Models\Color;
use App\Services\{SettingService, ProductService, CategoryService};
use App\Services\ProductColorService;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class ProductsImport implements OnEachRow, WithHeadingRow, WithValidation
{
    private int $created = 0;
    private int $updated = 0;
    private array $categoriesCache = [];
    private array $colorsCache = [];
    private array $productsCache = [];

    public function __construct(
        protected ProductService $productService,
        protected SettingService $settingService,
        protected CategoryService $categoryService,
        protected ProductColorService $productColorService
    ) {}

    /**
     * @param Row $row
     */
    public function onRow(Row $row)
    {
        $rowData = $row->toArray();
        $rowIndex = $row->getIndex();

        // Nettoyage des données d'entrée
        $name = mb_strtolower(trim($rowData['name']));
        //  Le prix est traité dans getPriceValue pour gérer les formats comme "8,400"
        $categoryName = isset($rowData['category']) ? mb_strtolower(trim($rowData['category'])) : null;
        // La couleur est traitée dans getColorIdByNameOrCreate pour gérer les variantes et les couleurs par défaut
        $colorName = isset($rowData['color']) ? mb_strtolower(trim($rowData['color'])) : null;

        try {
            $categoryId = $this->getCategoryIdByNameOrCreate($categoryName);

            $productId = $this->getProductIdByNameOrCreate(
                $name,
                (float)$rowData['price'],
                $categoryId
            );

            $colorId = $this->getColorIdByNameOrCreate($colorName);

            $this->processProductColor(
                $productId,
                $colorId,
                $rowData
            );
        } catch (\Throwable $e) {
            throw new \Exception("Erreur ligne {$rowIndex} : " . $e->getMessage());
        }
    }

    /**
     * Nettoie les données avant la validation.
     * Convertit par exemple "8,400" en 8400 pour que la règle 'numeric' passe.
     */
    public function prepareForValidation($data, $index)
    {
        if (isset($data['price'])) {
            $data['price'] = str_replace([' ', ','], '', (string)$data['price']);
        }
        if (isset($data['stock'])) {
            $data['stock'] = str_replace([' ', ','], '', (string)$data['stock']);
        }
        if (isset($data['color'])) {
            $data['color'] = (string)$data['color'];
        }
        return $data;
    }

    // Validation rules for each row of the import, ensuring data integrity and providing clear error messages for any issues encountered during the import process
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'price' => 'required|numeric|min:0',
            'color' => 'nullable|string',
            'alert_stock' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
        ];
    }
    // Function to generate a report of the import process, providing insights into the number of products created and updated, as well as any failures encountered during the import
    public function getReport(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failures' => 0,
            'failure_details' => [],
        ];
    }

    // function pour gerer le category, creer la catégorie si elle n'existe pas et retourner son id, cacher les catégories déjà vérifiées pour éviter les requêtes répétées
    private function getCategoryIdByNameOrCreate(?string $name): ?int
    {
        if (!$name) {
            return null;
        }

        $catName = $name; // Déjà nettoyé dans onRow

        if (array_key_exists($catName, $this->categoriesCache)) {
            return $this->categoriesCache[$catName];
        }

        $category = $this->categoryService->findOneBy(['name' => $catName]);

        if (!$category) {
            $category = $this->categoryService->create(['name' => $catName]);
        }

        $this->categoriesCache[$catName] = $category->id;

        return $category->id;
    }

    private function getProductIdByNameOrCreate(string $productName, float $price, ?int $categoryId): int
    {
        if (array_key_exists($productName, $this->productsCache)) {
            return $this->productsCache[$productName];
        }

        $existingProduct = $this->productService->findOneBy(['name' => $productName]);

        if ($existingProduct) {
            $this->productsCache[$productName] = $existingProduct->id;
            return $existingProduct->id;
        }

        $newProduct = $this->productService->create([
            'name' => $productName,
            'price' => $price,
            'category_id' => $categoryId,
        ]);

        $this->productsCache[$productName] = $newProduct->id;
        return $newProduct->id;
    }

    private function getColorIdByNameOrCreate(?string $colorName): ?int
    {
        if (!$colorName) {
            // Si vide, on vérifie si on a une couleur par défaut "No Variant" en cache
            if (array_key_exists('__default_no_variant__', $this->colorsCache)) {
                return $this->colorsCache['__default_no_variant__'];
            }

            // Sinon on la cherche en base (insensible à la casse)
            $noVariant = Color::whereRaw('LOWER(name) IN (?, ?)', ['no variant', 'sans variante'])->first();

            if ($noVariant) {
                $this->colorsCache['__default_no_variant__'] = $noVariant->id;
                return $noVariant->id;
            }

            return null;
        }

        // Cas où une couleur est spécifiée dans le fichier
        if (array_key_exists($colorName, $this->colorsCache)) {
            return $this->colorsCache[$colorName];
        }

        // Note: Utilisation de Color directement car ProductColorService gère les variantes, pas les noms de couleurs
        $color = Color::whereRaw('LOWER(name) = ?', [$colorName])->first();

        if (!$color) {
            $color = Color::create(['name' => $colorName, 'code' => $colorName]);
        }

        $this->colorsCache[$colorName] = $color->id;
        return $color->id;
    }

    private function processProductColor(int $productId, ?int $colorId, array $rowData)
    {
        // Chercher si cette variante (Produit + Couleur) existe déjà
        $variant = $this->productColorService->findOneBy([
            'product_id' => $productId,
            'color_id' => $colorId
        ]);

        $data = [
            'product_id' => $productId,
            'color_id' => $colorId,
            'price' => $this->getPriceValue($rowData['price']),
            'stock' => $this->getStockValue($rowData['stock'] ?? 0),
            'alert_stock' => $this->getAlertStockValue(isset($rowData['alert_stock']) ? (int)$rowData['alert_stock'] : null),
            'description' => $this->getDescriptionValue($rowData['description'] ?? null),
        ];

        if ($variant) {
            $this->productColorService->update($variant->id, $data);
            $this->updated++;
        } else {
            $this->productColorService->create($data);
            $this->created++;
        }
    }

    private function getStockValue(int $stock): int
    {
        return max(0, (int)$stock);
    }

    private function getAlertStockValue(?int $alertStock): int
    {
        if (is_null($alertStock) || $alertStock < 0) {
            return $this->settingService->get('global_alert_threshold') ?? 10;
        }
        return (int)$alertStock;
    }

    private function getPriceValue($price): float
    {
        return max(0, (float)$price);
    }

    private function getDescriptionValue(?string $description): ?string
    {
        if (is_null($description) || trim($description) === '') {
            return null;
        }
        return $description;
    }
}
