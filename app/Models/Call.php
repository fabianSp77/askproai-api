<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    /** Alle Spalten frei geben – einfachste Variante  */
    protected $guarded = [];

    /** Typ-Casts */
    protected $casts = [
        'analysis'        => 'array',
        'call_successful' => 'boolean',
    ];
}
