<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telefonnummer extends Model
{
    use HasFactory;

    protected $table = "telefonnummern";

    protected $fillable = [
        "kunde_id",
        "nummer",
        "beschreibung",
        "aktiv"
    ];

    protected $casts = [
        "aktiv" => "boolean",
    ];

    public function kunde(): BelongsTo
    {
        return $this->belongsTo(Kunde::class);
    }
}