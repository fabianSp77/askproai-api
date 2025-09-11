<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetellAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'phone_number_id', 'agent_id',
        'name', 'settings', 'active', 'is_active',
        'configuration', 'version', 'version_title',
        'is_published', 'sync_status', 'last_synced_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'configuration' => 'array',
        'active' => 'boolean',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }
}
