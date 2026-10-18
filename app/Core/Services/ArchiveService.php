<?php

namespace App\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Archivage, restauration et suppression définitive d'éléments archivables (App\Core\Models\Concerns\Archivable),
 * un par un ou par sélection.
 *
 * Chaque élément est traité séparément : un élément refusé (archiveBlocker(), élément déjà utilisé)
 * n'empêche pas les autres. Le résultat compte les éléments traités et donne la raison de chaque refus.
 */
class ArchiveService
{
    /**
     * @return array{done: int, skipped: array<int, string>}
     */
    public function archive(Builder $query): array
    {
        return $this->each($query->active(), function ($model) {
            if ($reason = $model->archiveBlocker()) {
                return $reason;
            }
            $model->archive();
        });
    }

    /**
     * @return array{done: int, skipped: array<int, string>}
     */
    public function restore(Builder $query): array
    {
        return $this->each($query->archived(), fn ($model) => $model->unarchive());
    }

    /**
     * Suppression définitive, réservée aux éléments archivés et jamais utilisés :
     * les clés étrangères (ON DELETE RESTRICT) refusent la suppression d'un élément cité ailleurs.
     *
     * @return array{done: int, skipped: array<int, string>}
     */
    public function forceDelete(Builder $query): array
    {
        return $this->each($query->archived(), function ($model) {
            try {
                // Transaction dédiée : un refus de PostgreSQL est annulé sans bloquer les éléments suivants
                DB::transaction(fn () => $model->delete());
            } catch (QueryException $e) {
                // SQLSTATE 23xxx : élément encore référencé (ventes, achats, mouvements, inventaires...)
                if (str_starts_with((string) $e->getCode(), '23')) {
                    return 'déjà utilisé, il reste archivé';
                }
                throw $e;
            }

            Log::info('Suppression définitive', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'label' => $model->archiveLabel(),
                'by_user_id' => auth()->id(),
            ]);
        });
    }

    /**
     * Applique $action à chaque élément. $action renvoie une raison de refus, ou null.
     *
     * @return array{done: int, skipped: array<int, string>}
     */
    private function each(Builder $query, callable $action): array
    {
        $done = 0;
        $skipped = [];

        foreach ($query->get() as $model) {
            $reason = $action($model);

            if (is_string($reason)) {
                $skipped[$model->getKey()] = "{$model->archiveLabel()} : {$reason}";
            } else {
                $done++;
            }
        }

        return ['done' => $done, 'skipped' => $skipped];
    }
}
