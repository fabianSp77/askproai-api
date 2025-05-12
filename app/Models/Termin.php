<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Termin extends Model
{
    use HasFactory;

    protected $table = "termine";

    protected $fillable = [
        "kunde_id",
        "mitarbeiter_id",
        "call_id",
        "anrufer_name",
        "anrufer_telefon",
        "anrufer_email",
        "datum",
        "uhrzeit",
        "dauer_minuten",
        "dienstleistung",
        "notizen",
        "cal_com_event_id",
        "status",
        "erinnerung_gesendet"
    ];

    protected $casts = [
        "datum" => "date",
        "uhrzeit" => "datetime",
        "erinnerung_gesendet" => "boolean",
    ];

    public function kunde(): BelongsTo
    {
        return $this->belongsTo(Kunde::class);
    }

    public function mitarbeiter(): BelongsTo
    {
        return $this->belongsTo(Mitarbeiter::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }
}