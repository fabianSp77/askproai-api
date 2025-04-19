<?php // app/Models/Tenant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt; // Für Verschlüsselung, falls Cal.com Key genutzt wird
use Illuminate\Database\Eloquent\Casts\Attribute; // Für Accessor/Mutator

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key', // Oder 'retell_agent_id' etc. anpassen!
        // 'calcom_api_key_encrypted', // Nur wenn benötigt
        // 'calcom_event_type_id', // Nur wenn benötigt
        'is_active',
    ];

    protected $hidden = [
        'api_key', // API Key nie direkt ausgeben
        // 'calcom_api_key_encrypted', // Verschlüsselten Key nie ausgeben
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Beziehung zu Calls
    public function calls(): HasMany { return $this->hasMany(\App\Models\Call::class); } // Sicherstellen, dass Call-Model korrekt ist

    // --- Beispiel für sicheres Handling des Cal.com API Keys (NUR WENN BENÖTIGT) ---
    /*
    protected function calcomApiKey(): Attribute
    {
        return Attribute::make(
            // Getter: Entschlüsselt den Key beim Zugriff
            get: fn ($value, $attributes) => isset($attributes['calcom_api_key_encrypted']) ? Crypt::decryptString($attributes['calcom_api_key_encrypted']) : null,
            // Setter: Verschlüsselt den Key beim Speichern
            set: fn ($value) => ['calcom_api_key_encrypted' => isset($value) ? Crypt::encryptString($value) : null],
        );
    }
    */
    // --- Ende Beispiel ---

    // Ggf. Beziehung zu Users, Appointments etc.
}
