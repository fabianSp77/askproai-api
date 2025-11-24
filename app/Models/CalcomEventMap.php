<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalcomEventMap extends Model
{
    use HasFactory, BelongsToCompany;
    protected $table = 'calcom_event_map';

    protected $fillable = [
        'company_id',
        'branch_id',
        'service_id',
        'segment_key',
        'staff_id',
        'event_type_id',
        'event_type_slug',
        'child_event_type_id',
        'child_resolved_at',
        'hidden',
        'event_name_pattern',
        'external_changes',
        'drift_data',
        'drift_detected_at',
        'sync_status',
        'last_sync_at',
        'sync_error',
    ];

    protected $casts = [
        'hidden' => 'boolean',
        'drift_data' => 'array',
        'drift_detected_at' => 'datetime',
        'child_resolved_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Branch relationship
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Service relationship
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Staff relationship
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Check if drift is detected
     */
    public function hasDrift(): bool
    {
        return !empty($this->drift_data) && $this->drift_detected_at !== null;
    }

    /**
     * Check if sync is needed
     */
    public function needsSync(): bool
    {
        return $this->sync_status === 'pending' ||
               $this->sync_status === 'error' ||
               ($this->last_sync_at && $this->last_sync_at->diffInHours(now()) > 24);
    }

    /**
     * Generate event name based on pattern
     */
    public function generateEventName(): string
    {
        // Pattern: COMPANY-BRANCH-SERVICE-SEGMENT-STAFF
        $parts = [
            strtoupper(substr($this->company->slug ?? 'NA', 0, 4)),
            strtoupper(substr($this->branch->slug ?? 'NA', 0, 3)),
            strtoupper(substr($this->service->slug ?? 'NA', 0, 5)),
            $this->segment_key ?? '',
            $this->staff ? 'S' . $this->staff->id : ''
        ];

        return implode('-', array_filter($parts));
    }

    /**
     * Scope for mappings with drift
     */
    public function scopeWithDrift($query)
    {
        return $query->whereNotNull('drift_detected_at');
    }

    /**
     * Scope for pending sync
     */
    public function scopePendingSync($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Scope for failed sync
     */
    public function scopeFailedSync($query)
    {
        return $query->where('sync_status', 'error');
    }
}