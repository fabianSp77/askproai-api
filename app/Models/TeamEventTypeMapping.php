<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamEventTypeMapping extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'calcom_team_id',
        'calcom_event_type_id',
        'event_type_name',
        'event_type_slug',
        'duration_minutes',
        'is_team_event',
        'hosts',
        'metadata',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'is_team_event' => 'boolean',
        'is_active' => 'boolean',
        'hosts' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns this team event type mapping
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the associated service if one exists
     */
    public function service()
    {
        return Service::where('calcom_event_type_id', $this->calcom_event_type_id)->first();
    }

    /**
     * Check if this event type has an associated service
     */
    public function hasService(): bool
    {
        return Service::where('calcom_event_type_id', $this->calcom_event_type_id)->exists();
    }
}