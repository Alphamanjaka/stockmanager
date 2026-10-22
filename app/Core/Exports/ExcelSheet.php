<?php

namespace App\Core\Exports;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Version Excel d'un export de liste (ListExport) : mêmes colonnes que le PDF, valeurs brutes
 * (nombres et dates réels, triables et additionnables), en-tête figé et filtres automatiques.
 */
class ExcelSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStrictNullComparison, WithStyles, WithTitle
{
    /** @param list<Column> $columns */
    public function __construct(
        private string $title,
        private array $columns,
        private Collection $rows,
    ) {}

    public function array(): array
    {
        return $this->rows->map(fn ($row) => array_map(fn (Column $column) => $this->cell($column, $column->value($row)), $this->columns))
            ->all();
    }

    public function headings(): array
    {
        return array_map(fn (Column $column) => $column->label, $this->columns);
    }

    /** Nom de l'onglet : 31 caractères au plus, sans les caractères interdits par Excel. */
    public function title(): string
    {
        return Str::limit(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $this->title), 31, '');
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->columns as $index => $column) {
            $format = match ($column->format()) {
                'money' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'number' => '#,##0',
                'date' => 'dd/mm/yyyy',
                'datetime' => 'dd/mm/yyyy hh:mm',
                default => null,
            };

            if ($format) {
                $formats[Coordinate::stringFromColumnIndex($index + 1)] = $format;
            }
        }

        return $formats;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(count($this->columns));

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastColumn}".max(1, $this->rows->count() + 1));
            },
        ];
    }

    private function cell(Column $column, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($column->isDate() && $value instanceof DateTimeInterface) {
            return Date::dateTimeToExcel($value);
        }
        if ($column->isNumeric() && is_numeric($value)) {
            return $value + 0;
        }
        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        return (string) $value;
    }
}
