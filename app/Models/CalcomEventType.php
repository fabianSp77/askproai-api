<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalcomEventType extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'staff_id',
        'service_id',
        'calcom_event_type_id',
        'duration',
        'calendar',
        'tenant_id',
        'name',
        'slug',
        'calcom_numeric_event_type_id',
        'duration_minutes',
        'description',
        'price',
        'is_active',
        'is_team_event',
        'requires_confirmation',
        'booking_limits',
        'sync_status',
        'sync_error',
        'last_synced_at',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Mitarbeiter, die diesem Event-Type zugeordnet sind
     */
    public function assignedStaff(): BelongsToMany
    {
        return $this->belongsToMany(
            Staff::class,
            'staff_event_types',
            'event_type_id',
            'staff_id'
        )
        ->using(StaffEventType::class)
        ->withPivot([
            'calcom_user_id',
            'is_primary',
            'custom_duration',
            'custom_price',
            'availability_override'
        ])
        ->withTimestamps();
    }
    
    /**
     * Alias für assignedStaff() für Backward Compatibility
     */
    public function staff(): BelongsToMany
    {
        return $this->assignedStaff();
    }

    /**
     * Scope für aktive Event-Types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für synchronisierte Event-Types
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Prüft ob Event-Type verfügbare Mitarbeiter hat
     */
    public function hasAvailableStaff(): bool
    {
        return $this->assignedStaff()->where('active', true)->exists();
    }

    /**
     * Gibt verfügbare Mitarbeiter für einen Zeitraum zurück
     */
    public function getAvailableStaff($startTime, $endTime)
    {
        return $this->assignedStaff()
            ->where('active', true)
            ->where('is_bookable', true)
            // Hier könnte eine Verfügbarkeitsprüfung ergänzt werden
            ->get();
    }
    
    /**
     * Beziehung zu Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Beziehung zu Branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    
    /**
     * Validiere ob Event-Type zur Branch passt
     */
    public function validateBranchAssignment(): bool
    {
        if (!$this->branch_id) {
            return false;
        }
        
        // Prüfe ob Branch zur Company gehört
        return $this->branch->company_id === $this->company_id;
    }
    
    /**
     * Beziehung zu Bookings
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(CalcomBooking::class, 'event_type_id');
    }
}
