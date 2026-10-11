<?php

namespace App\Modules\Contact\Models;

use App\Modules\Contact\Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Client final. Les ventes qui lui sont rattachées appartiennent au module Sale (sales.customer_id).
 */
#[UseFactory(CustomerFactory::class)]
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function scopeSearch($query, string $term)
    {
        $query->where(function ($query) use ($term) {
            $query->whereLike('name', "%{$term}%")
                ->orWhereLike('phone', "%{$term}%")
                ->orWhereLike('email', "%{$term}%");
        });
    }
}
