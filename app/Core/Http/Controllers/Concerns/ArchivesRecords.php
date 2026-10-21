<?php

namespace App\Core\Http\Controllers\Concerns;

use App\Core\Services\ArchiveService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Archivage depuis une liste : « Archiver » et « Restaurer » pour un élément (menu de la ligne)
 * ou une sélection (cases à cocher). Les deux envoient ids[] (voir <x-ui.bulk-bar> et <x-ui.row-menu>).
 * Suppression définitive : réservée à un élément archivé que rien n'utilise (deletePermanently()).
 *
 * Le contrôleur indique les éléments visés par les identifiants reçus :
 *
 *     protected function archiveTargets(array $ids): Builder
 *     {
 *         return Brand::whereKey($ids);
 *     }
 *
 * Routes (avant la ressource, pour que /brands/archive ne soit pas lu comme /brands/{brand}) :
 *
 *     Route::post('/brands/archive', [BrandController::class, 'archive'])->name('brands.archive');
 *     Route::post('/brands/restore', [BrandController::class, 'restore'])->name('brands.restore');
 */
trait ArchivesRecords
{
    /** Taille maximale d'une sélection traitée en une fois. */
    protected int $maxSelection = 500;

    /** Éléments archivables correspondant aux identifiants envoyés par la liste. */
    abstract protected function archiveTargets(array $ids): Builder;

    public function archive(Request $request, ArchiveService $archives): RedirectResponse
    {
        if (is_string($ids = $this->selectedIds($request))) {
            return back()->with('error', $ids);
        }

        return $this->archiveResponse($archives->archive($this->archiveTargets($ids)), 'archivé(s)', 'Non archivé(s)');
    }

    public function restore(Request $request, ArchiveService $archives): RedirectResponse
    {
        if (is_string($ids = $this->selectedIds($request))) {
            return back()->with('error', $ids);
        }

        return $this->archiveResponse($archives->restore($this->archiveTargets($ids)), 'restauré(s)', 'Non restauré(s)');
    }

    /**
     * Suppression définitive d'un élément archivé (action « Supprimer définitivement » des archives).
     * La base la refuse si l'élément est cité ailleurs (ventes, achats, mouvements...) : il reste archivé.
     */
    protected function deletePermanently(int $id, string $redirectRoute): RedirectResponse
    {
        $result = app(ArchiveService::class)->forceDelete($this->archiveTargets([$id]));

        if ($result['done'] > 0) {
            return redirect()->route($redirectRoute, ['status' => 'archived'])
                ->with('success', 'Élément supprimé définitivement.');
        }

        return back()->with('error', $result['skipped']
            ? 'Suppression impossible : '.implode(' ; ', $result['skipped']).'.'
            : 'Seul un élément archivé peut être supprimé définitivement : archivez-le d\'abord.');
    }

    /**
     * Identifiants de la sélection, ou le message d'erreur à afficher (les listes affichent les messages
     * de session, pas les erreurs de validation).
     *
     * @return list<int>|string
     */
    protected function selectedIds(Request $request): array|string
    {
        $ids = array_filter((array) $request->input('ids', []), fn ($id) => is_scalar($id) && ctype_digit((string) $id));

        if (! $ids) {
            return 'Aucun élément sélectionné.';
        }
        if (count($ids) > $this->maxSelection) {
            return "Sélection trop grande : {$this->maxSelection} éléments au maximum.";
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /** @param array{done: int, skipped: array<int, string>} $result */
    private function archiveResponse(array $result, string $done, string $refused): RedirectResponse
    {
        $response = back();

        if ($result['done'] > 0) {
            $response->with('success', "{$result['done']} élément(s) {$done}.");
        }
        if ($result['skipped']) {
            $response->with('error', "{$refused} : ".implode(' ; ', $result['skipped']).'.');
        } elseif ($result['done'] === 0) {
            $response->with('error', 'Aucun élément à traiter : la sélection a peut-être déjà été traitée.');
        }

        return $response;
    }
}
