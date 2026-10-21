<?php

namespace App\Core\Exports;

use Closure;
use DateTimeInterface;

/**
 * Colonne d'un export de liste (PDF et Excel) : libellé, valeur lue sur la ligne, format.
 *
 *     Column::make('Product', fn ($variant) => $variant->label),
 *     Column::make('Stock', 'stock')->number()->total(),
 *     Column::make('Price', 'price')->money(),
 *     Column::make('Created', 'created_at')->date(),
 *
 * La valeur brute part dans Excel (nombres, dates réelles : triables, additionnables) ;
 * le PDF affiche la valeur formatée (devise des Paramètres, séparateurs de milliers).
 */
final class Column
{
    private string $format = 'text';

    private bool $total = false;

    private ?string $width = null;

    private function __construct(
        public readonly string $label,
        private readonly string|Closure $value,
    ) {}

    /** $value : attribut (notation pointée acceptée : 'product.category.name') ou fonction de la ligne. */
    public static function make(string $label, string|Closure $value): self
    {
        return new self($label, $value);
    }

    public function number(): self
    {
        $this->format = 'number';

        return $this;
    }

    public function money(): self
    {
        $this->format = 'money';

        return $this;
    }

    public function date(): self
    {
        $this->format = 'date';

        return $this;
    }

    public function datetime(): self
    {
        $this->format = 'datetime';

        return $this;
    }

    /** Somme de la colonne affichée en pied de tableau (colonnes numériques). */
    public function total(): self
    {
        $this->total = true;

        return $this;
    }

    /** Largeur de la colonne dans le PDF (ex. '12%'). */
    public function width(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function value(mixed $row): mixed
    {
        return $this->value instanceof Closure ? ($this->value)($row) : data_get($row, $this->value);
    }

    /** Texte affiché dans le PDF. */
    public function display(mixed $row): string
    {
        return $this->formatValue($this->value($row));
    }

    public function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($this->format) {
            'number' => number_format((float) $value, is_float($value) && floor($value) != $value ? 2 : 0),
            'money' => money($value),
            'date' => $value instanceof DateTimeInterface ? $value->format('d/m/Y') : (string) $value,
            'datetime' => $value instanceof DateTimeInterface ? $value->format('d/m/Y H:i') : (string) $value,
            default => is_bool($value) ? ($value ? __('Yes') : __('No')) : (string) $value,
        };
    }

    public function isNumeric(): bool
    {
        return in_array($this->format, ['number', 'money'], true);
    }

    public function isDate(): bool
    {
        return in_array($this->format, ['date', 'datetime'], true);
    }

    public function format(): string
    {
        return $this->format;
    }

    public function hasTotal(): bool
    {
        return $this->total;
    }

    public function pdfWidth(): ?string
    {
        return $this->width;
    }
}
