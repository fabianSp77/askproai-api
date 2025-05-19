<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Call extends Model
{
    use HasFactory;

    /** Alle Spalten frei geben – einfachste Variante  */
    protected $guarded = [];

    /** Typ-Casts */
    protected $casts = [
        'analysis'        => 'array',
        'call_successful' => 'boolean',
    ];
}
