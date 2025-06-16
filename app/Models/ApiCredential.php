<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'credentialable_id',
        'credentialable_type',
        'service',
        'key_type',
        'value',
        'is_inherited',
        'inherited_from_id',
        'inherited_from_type'
    ];

    protected $casts = [
        'is_inherited' => 'boolean',
    ];

    /**
     * Get the parent credentialable model (company, branch or staff).
     */
    public function credentialable()
    {
        return $this->morphTo();
    }

    /**
     * Get the model this credential was inherited from
     */
    public function inheritedFrom()
    {
        return $this->morphTo('inherited_from');
    }

    /**
     * Scope to get credentials for a specific service
     */
    public function scopeForService($query, $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope to get credentials of a specific type
     */
    public function scopeOfType($query, $keyType)
    {
        return $query->where('key_type', $keyType);
    }

    /**
     * Get credential value, checking inheritance if necessary
     */
    public function getEffectiveValue()
    {
        if ($this->is_inherited && $this->inheritedFrom) {
            return $this->inheritedFrom->apiCredentials()
                ->where('service', $this->service)
                ->where('key_type', $this->key_type)
                ->first()?->value;
        }
        return $this->value;
    }
}
