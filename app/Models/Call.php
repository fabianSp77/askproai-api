<?php // app/Models/Call.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Scopes\TenantScope; // <-- Scope importieren

class Call extends Model
{
    use HasFactory;

    protected $fillable = [ 'call_id', 'tenant_id', 'call_status', 'phone_number', 'call_duration', 'transcript', 'summary', 'user_sentiment', 'successful', 'disconnect_reason', 'raw_data', 'kunde_id', 'name', 'email', 'cost', 'type', 'call_time', ];
    protected $casts = [ 'raw_data' => 'array', 'successful' => 'boolean', 'call_duration' => 'integer', 'call_time' => 'datetime', 'cost' => 'decimal:2', 'created_at' => 'datetime', 'updated_at' => 'datetime', ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void // <-- Methode ist jetzt aktiv
    {
        static::addGlobalScope(new TenantScope);
    }

    // --- Beziehungen ---
    public function tenant(): BelongsTo { return $this->belongsTo(\App\Models\Tenant::class); }
    public function customer(): BelongsTo { return $this->belongsTo(\App\Models\Kunde::class, 'kunde_id'); }
    /* public function appointments(): HasMany { ... } */
}
