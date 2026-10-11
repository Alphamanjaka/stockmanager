<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type'];

    // Helper to retrieve values by key could go here, but we'll use a Service.
}
