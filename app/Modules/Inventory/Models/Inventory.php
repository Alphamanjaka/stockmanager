<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Model;

/**
 * Campagne de comptage physique. Brouillon tant qu'elle n'est pas validée ;
 * la validation ajuste le stock des lignes comptées et la rend définitive.
 */
class Inventory extends Model
{
    public const DRAFT = 'draft';
    public const VALIDATED = 'validated';

    protected $fillable = [
        'reference',
        'status',
        'category_id',
        'notes',
        'user_id',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }

    public function lines()
    {
        return $this->hasMany(InventoryLine::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }
}
