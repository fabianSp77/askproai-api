<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Call extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Korrekte Felder für die Tabelle calls
    protected $casts = [
        'analysis'        => 'array',
        'raw'             => 'array',
        'details'         => 'array',
        'call_successful' => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];
}
