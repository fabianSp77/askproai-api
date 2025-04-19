<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Call extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * ACHTUNG: Passe diese Liste an die tatsächlichen Spalten an,
     * die per Mass Assignment befüllt werden sollen/dürfen.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'call_id',          // Eindeutige ID von Retell.ai
        'call_status',      // KORRIGIERT: Name der DB-Spalte (vorher 'status')
        'phone_number',
        'call_duration',    // KORRIGIERT: Name der DB-Spalte (vorher 'duration')
        'transcript',
        'summary',
        'user_sentiment',
        'successful',       // KORRIGIERT: Name der DB-Spalte (vorher 'call_successful')
        'disconnect_reason',
        'raw_data',         // Original-Payload von Retell.ai
        'kunde_id',         // Fremdschlüssel zur (alten?) Kunden-Tabelle
        'name',             // Name, falls im Webhook übergeben
        'email',            // E-Mail, falls im Webhook übergeben
        // Füge hier ggf. weitere Felder hinzu, die befüllt werden sollen
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'raw_data' => 'array',   // JSON-Daten automatisch in Array umwandeln
        'successful' => 'boolean', // KORRIGIERT: Spaltenname angepasst
        'call_duration' => 'integer', // KORRIGIERT: Spaltenname angepasst
        // 'call_time' => 'datetime', // Falls es eine call_time Spalte gäbe
    ];

    /**
     * Get the customer (Kunde) that owns the call.
     * ACHTUNG: Stellt sicher, dass das Customer Model existiert und korrekt verknüpft ist.
     *          Das Original verwendete 'Customer::class', was zum Standard Laravel Auth gehört.
     *          Wenn du das `Kunde`-Model meinst, ändere es zu `Kunde::class`.
     */
    public function kunde(): BelongsTo
    {
        // Prüfe, ob das 'Kunde' Model oder ein anderes verwendet werden soll.
        // return $this->belongsTo(Kunde::class, 'kunde_id'); // Beispiel, falls Kunde Model gemeint ist
        return $this->belongsTo(Customer::class, 'kunde_id'); // Beibehaltung der Original-Referenz
    }

    /**
     * Get the appointments for the call.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
