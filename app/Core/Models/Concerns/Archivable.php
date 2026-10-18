<?php

namespace App\Core\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Archivage : l'alternative sûre à la suppression des données de référence
 * (produits, catégories, marques, couleurs, fournisseurs, clients, utilisateurs).
 *
 * Un élément archivé disparaît des listes et des listes de choix, mais reste en base :
 * les ventes, achats, mouvements et inventaires qui le citent restent complets.
 * Il se restaure à tout moment. Seul un élément jamais utilisé peut ensuite être supprimé définitivement.
 *
 * Volontairement sans scope global (contrairement à SoftDeletes) : une relation vers un élément archivé
 * (ligne de facture → variante d'un produit archivé) doit continuer à le renvoyer.
 * Les listes et les listes de choix filtrent donc explicitement avec ->active().
 *
 * Le modèle peut définir archiveBlocker() pour refuser l'archivage (ex. catégorie qui contient
 * encore des produits actifs).
 */
trait Archivable
{
    public function initializeArchivable(): void
    {
        $this->mergeCasts(['archived_at' => 'datetime']);
    }

    /** Éléments en service (non archivés). */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull($query->qualifyColumn('archived_at'));
    }

    /** Éléments archivés. */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull($query->qualifyColumn('archived_at'));
    }

    /** Filtre de liste : 'archived' (archives) ou les éléments actifs (par défaut). */
    public function scopeWithStatus(Builder $query, ?string $status): void
    {
        $status === 'archived' ? $query->archived() : $query->active();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(): void
    {
        $this->forceFill(['archived_at' => now()])->save();
    }

    public function unarchive(): void
    {
        $this->forceFill(['archived_at' => null])->save();
    }

    /** Raison qui empêche l'archivage, ou null s'il est possible. */
    public function archiveBlocker(): ?string
    {
        return null;
    }

    /** Nom affiché dans les messages (« Tissus Mada ne peut pas être archivé : … »). */
    public function archiveLabel(): string
    {
        return (string) ($this->name ?? "#{$this->getKey()}");
    }
}
