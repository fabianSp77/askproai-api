<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integration extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'type',
        'status',
        'config',
        'credentials',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'config' => 'array',
        'credentials' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    // Relationships based on actual database schema
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
