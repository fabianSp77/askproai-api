<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Scopes\TenantScope;

class PhoneNumber extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'branch_id',
        'number',
        'formatted_number',
        'type',
        'routing_config',
        'agent_id',
        'active',
        'is_active',
        'description',
        'is_primary',
        'sms_enabled',
        'whatsapp_enabled'
    ];
    
    protected $casts = [
        'routing_config' => 'array',
        'active' => 'boolean',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'sms_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean'
    ];
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }
    
    /**
     * Get the company that owns the phone number
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns the phone number
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id', 'id');
    }
    
    /**
     * Scope for active phone numbers
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->where('active', true)
              ->orWhere('is_active', true);
        });
    }
    
    /**
     * Scope for hotline numbers
     */
    public function scopeHotlines($query)
    {
        return $query->where('type', 'hotline');
    }
    
    /**
     * Scope for direct numbers
     */
    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }
    
    /**
     * Check if this is a hotline number
     */
    public function isHotline(): bool
    {
        return $this->type === 'hotline';
    }
    
    /**
     * Get routing strategy
     */
    public function getRoutingStrategy(): string
    {
        return $this->routing_config['strategy'] ?? 'default';
    }
    
    /**
     * Get menu options for hotline
     */
    public function getMenuOptions(): array
    {
        return $this->routing_config['menu_options'] ?? [];
    }
}
