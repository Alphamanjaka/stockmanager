<?php

namespace App\Core\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base de tous les exports de liste (PDF et Excel) : chaque liste du back office a le sien,
 * dans son module (app/Modules/<Module>/Exports/), déclaré dans la propriété $exports du provider.
 *
 * Un export ne décrit que ce qui lui est propre :
 *
 *     class BrandsExport extends ListExport
 *     {
 *         public function title(): string { return 'Brands'; }
 *
 *         public function columns(): array
 *         {
 *             return [Column::make('Name', 'name'), Column::make('Variants', 'variants_count')->number()];
 *         }
 *
 *         protected function query(array $filters): Builder
 *         {
 *             return Brand::listQuery($filters);   // la même requête que la page
 *         }
 *     }
 *
 * La base fournit le reste : mêmes filtres que la page (recherche, statut, tri...), export de la sélection
 * (ids[] des cases à cocher), en-tête de l'entreprise, filtres appliqués, totaux, numéros de page,
 * et le même contenu en Excel (valeurs brutes : nombres et dates exploitables).
 */
abstract class ListExport
{
    /** Orientation du PDF : 'landscape' pour les listes larges. */
    protected string $orientation = 'portrait';

    /**
     * Au-delà, le PDF n'affiche que les premières lignes : dompdf met ~1 s pour 100 lignes (bien plus avec
     * Xdebug), et la requête doit rester sous la limite d'exécution de PHP. L'Excel reste complet.
     */
    protected int $maxPdfRows = 1000;

    /** Titre du document (et du fichier). */
    abstract public function title(): string;

    /** @return list<Column> */
    abstract public function columns(): array;

    /**
     * Lignes de la liste avec ses filtres (paramètres de la page : search, status, sort, order...).
     * Idéalement la même requête que la page, pour exporter exactement ce qui est affiché.
     */
    abstract protected function query(array $filters): Builder;

    /**
     * Filtres propres à la liste, en clair pour l'en-tête du document : ['Category' => 'Wigs'].
     * La recherche et le statut (archives) sont déjà décrits par la base.
     *
     * @return array<string, string>
     */
    protected function filterLabels(array $filters): array
    {
        return [];
    }

    /**
     * Chiffres clés affichés au-dessus du tableau : ['Stock value' => '1,200,000.00 MGA'].
     *
     * @return array<string, string>
     */
    protected function summary(Collection $rows): array
    {
        return [];
    }

    /** @param list<int> $ids sélection (cases à cocher) ; vide : toute la liste filtrée */
    public function rows(array $filters, array $ids = [], ?int $limit = null): Collection
    {
        $query = $this->query($filters);

        if ($ids) {
            $this->applySelection($query, $ids);
        }

        return $query->when($limit, fn ($q) => $q->limit($limit))->get();
    }

    /**
     * Restreint la requête à la sélection. Par défaut, les cases portent la clé de la ligne ;
     * un export dont les cases portent une autre clé la redéfinit (variantes cochées par produit).
     */
    protected function applySelection(Builder $query, array $ids): void
    {
        $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids);
    }

    /** @return array<string, string> filtres appliqués, en clair */
    public function describeFilters(array $filters, array $ids = []): array
    {
        $described = [];

        if ($ids) {
            $described['Selection'] = count($ids).' item(s)';
        }
        if (filled($filters['search'] ?? null)) {
            $described['Search'] = '« '.$filters['search'].' »';
        }

        $described += array_filter($this->filterLabels($filters), 'filled');

        if (($filters['status'] ?? null) === 'archived') {
            $described['Status'] = 'Archived';
        }

        return $described;
    }

    public function filename(string $extension): string
    {
        return Str::slug($this->title()).'-'.now()->format('Y-m-d').".{$extension}";
    }

    public function toPdf(array $filters, array $ids = []): Response
    {
        // Une ligne de plus que la limite : elle indique que la liste a été tronquée
        $rows = $this->rows($filters, $ids, $this->maxPdfRows + 1);
        $truncated = $rows->count() > $this->maxPdfRows;
        $rows = $rows->take($this->maxPdfRows);
        $columns = $this->columns();

        $pdf = PdfDocument::load('core::exports.list', [
            'title' => $this->title(),
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $this->totals($columns, $rows),
            'summary' => $this->summary($rows),
            'filters' => $this->describeFilters($filters, $ids),
            'truncated' => $truncated ? $this->maxPdfRows : null,
        ], $this->orientation);

        return $pdf->download($this->filename('pdf'));
    }

    public function toExcel(array $filters, array $ids = []): Response
    {
        return Excel::download(
            new ExcelSheet($this->title(), $this->columns(), $this->rows($filters, $ids)),
            $this->filename('xlsx')
        );
    }

    /** @return array<int, string> total formaté par position de colonne (colonnes ->total()) */
    private function totals(array $columns, Collection $rows): array
    {
        $totals = [];

        foreach ($columns as $index => $column) {
            if ($column->hasTotal()) {
                $totals[$index] = $column->formatValue($rows->sum(fn ($row) => (float) $column->value($row)));
            }
        }

        return $totals;
    }
}
