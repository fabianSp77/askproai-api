<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Import hinzugefügt

class Kunde extends Model
{
    use HasFactory;

    /**
     * Der Datenbanktabelle, die mit dem Model verknüpft ist.
     *
     * @var string
     */
    // Korrekter Tabellenname basierend auf deiner Ausgabe
    protected $table = 'kunden';

    /**
     * Die Attribute, die massenhaft zuweisbar sind.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'telefonnummer',
        'notizen',
        // Füge ggf. weitere Felder hinzu, die über create/update befüllt werden
    ];

    /**
     * Definiert die Beziehung zu den Calls.
     */
    public function calls(): HasMany
    {
        // Nimmt an, dass die 'calls'-Tabelle eine 'kunde_id'-Spalte hat.
        return $this->hasMany(Call::class, 'kunde_id');
    }

    /**
     * Definiert die Beziehung zu den Appointments.
     */
    public function appointments(): HasMany
    {
        // Nimmt an, dass die 'appointments'-Tabelle eine 'kunde_id'-Spalte hat.
        // Stelle sicher, dass das App\Models\Appointment Model existiert.
        // Wenn das Appointment-Model nicht existiert oder anders heißt, passe es hier an.
        // return $this->hasMany(Appointment::class, 'kunde_id');
        // Da wir kein Appointment-Model haben, kommentieren wir dies vorerst aus
        return $this->hasMany(\App\Models\Appointment::class, 'kunde_id'); // Annahme des Namens, prüfen!

    }
}
