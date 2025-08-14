<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dienstleistung extends Model
{
    use HasFactory;

    protected $table = 'dienstleistungen';

    protected $fillable = [
        'kunde_id',
        'name',
        'dauer_minuten',
        'preis',
        'cal_com_event_type_id',
        'beschreibung',
        'aktiv',
    ];

    protected $casts = [
        'preis' => 'decimal:2',
        'aktiv' => 'boolean',
    ];

    public function kunde(): BelongsTo
    {
        return $this->belongsTo(Kunde::class);
    }

    public function mitarbeiter(): BelongsToMany
    {
        return $this->belongsToMany(Mitarbeiter::class, 'mitarbeiter_dienstleistungen');
    }
}
