<?php

namespace App\Core\Exports;

/**
 * Exports de liste déclarés par les modules (propriété $exports de leur provider) :
 * ['products' => ProductsExport::class]. La clé est celle de l'URL (admin/exports/products/pdf).
 */
class ExportRegistry
{
    /** @var array<string, class-string<ListExport>> */
    private array $exports = [];

    /** @param array<string, class-string<ListExport>> $exports */
    public function add(array $exports): void
    {
        $this->exports = array_merge($this->exports, $exports);
    }

    public function has(string $key): bool
    {
        return isset($this->exports[$key]);
    }

    public function make(string $key): ?ListExport
    {
        return $this->has($key) ? app($this->exports[$key]) : null;
    }

    /** @return array<string, class-string<ListExport>> */
    public function all(): array
    {
        return $this->exports;
    }
}
