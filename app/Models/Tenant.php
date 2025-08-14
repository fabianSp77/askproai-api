<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class Tenant extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    // API key security: store hashed, generate plain
    protected $fillable = ['name', 'slug'];
    protected $hidden = ['api_key_hash'];

    /* -------------------------------------------------------------------- */
    protected static function booted(): void
    {
        static::creating(function (self $tenant) {
            $tenant->id   ??= (string) Str::uuid();
            $tenant->slug ??= Str::slug($tenant->name);
            
            // Generate plain API key only on creation
            if (empty($tenant->api_key_hash)) {
                $plainApiKey = 'ask_' . Str::random(32);
                $tenant->api_key_hash = Hash::make($plainApiKey);
                
                // Store plain key temporarily for initial response
                $tenant->setRawAttribute('plain_api_key', $plainApiKey);
            }
        });
    }

    /**
     * Verify API key against hash
     */
    public function verifyApiKey(string $plainKey): bool
    {
        return Hash::check($plainKey, $this->api_key_hash);
    }

    /**
     * Generate new API key (returns plain key, stores hash)
     */
    public function regenerateApiKey(): string
    {
        $plainApiKey = 'ask_' . Str::random(32);
        $this->api_key_hash = Hash::make($plainApiKey);
        $this->save();
        
        return $plainApiKey;
    }

    /**
     * Find tenant by API key (secure lookup)
     */
    public static function findByApiKey(string $plainKey): ?self
    {
        // Get all tenants and verify hash (secure but not scalable)
        foreach (self::all() as $tenant) {
            if ($tenant->verifyApiKey($plainKey)) {
                return $tenant;
            }
        }
        
        return null;
    }

    /* -------------------------------------------------------------------- */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
