<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mitarbeiter extends Model
{
    protected $fillable = [
        'kunden_id',
        'vorname',
        'nachname',
        'email',
        'telefonnummer',
        'kalender_verfuegbarkeit',
    ];

    protected $casts = [
        'kalender_verfuegbarkeit' => 'array',
    ];

    public function kunde(): BelongsTo
    {
        return $this->belongsTo(Kunde::class, 'kunden_id');
    }
}
//
