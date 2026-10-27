<?php

namespace App\Modules\Contact\Models;

use App\Core\Models\Concerns\Archivable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Base commune des contacts (fournisseurs, clients) : mêmes coordonnées, même recherche.
 *
 * Classe abstraite, sans table : chaque contact garde sa propre table (suppliers, customers)
 * et n'y ajoute que ce qui lui est propre (relations, factory, délai de livraison d'un fournisseur). Les règles de validation,
 * qui diffèrent (téléphone obligatoire et email unique pour un fournisseur), restent dans
 * les requêtes de chaque contact.
 *
 * Un contact avec qui l'on ne travaille plus s'archive : ses achats ou ses ventes restent complets.
 */
abstract class Contact extends Model
{
    use Archivable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    /** Recherche par nom, téléphone ou email (insensible à la casse). */
    public function scopeSearch($query, string $term)
    {
        $query->where(function ($query) use ($term) {
            $query->whereLike('name', "%{$term}%")
                ->orWhereLike('phone', "%{$term}%")
                ->orWhereLike('email', "%{$term}%");
        });
    }
}
