<?php

namespace App\Models;

use App\Traits\BelongsToCompany;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\TenantScope;

class Staff extends Model
{
    use BelongsToCompany;

    use HasUuids, SoftDeletes, HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'company_id',
        'branch_id',
        'home_branch_id',
        'name',
        'email',
        'phone',
        'external_id',
        'active',
        'is_bookable',
        'calendar_mode',
        'calcom_user_id',
        'calcom_event_type_id',
        'calcom_calendar_link',
        'availability_mode',
        'workable_branches',
        'notes',
        'external_calendar_id',
        'calendar_provider',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_bookable' => 'boolean',
        'workable_branches' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function homeBranch()
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Many-to-Many Beziehung zu Branches
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'staff_branches')
            ->withTimestamps();
    }

    // Many-to-Many Beziehung zu Services
    public function services()
    {
        return $this->belongsToMany(Service::class, 'staff_services')
            ->withTimestamps();
    }

    // One-to-Many Beziehung zu WorkingHours
    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class);
    }

    // One-to-Many Beziehung zu Appointments
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Many-to-Many Beziehung zu Event Types
    public function eventTypes()
    {
        return $this->belongsToMany(CalcomEventType::class, 'staff_event_types', 'staff_id', 'event_type_id')
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
     * Check if staff can host a specific event type
     * 
     * @param int|string $eventTypeId Can be internal ID or Cal.com ID
     * @return bool
     */
    public function canHostEventType($eventTypeId): bool
    {
        // Check by internal ID first
        if (is_numeric($eventTypeId)) {
            return $this->eventTypes()
                ->where('calcom_event_types.id', $eventTypeId)
                ->exists();
        }
        
        // Check by Cal.com event type ID
        return $this->eventTypes()
            ->where('calcom_event_type_id', $eventTypeId)
            ->exists();
    }
    
    /**
     * Get event types this staff can host
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHostableEventTypes()
    {
        return $this->eventTypes()
            ->where('calcom_event_types.is_active', true)
            ->get();
    }
    
    /**
     * Check if staff has any event type assignments
     * 
     * @return bool
     */
    public function hasEventTypeAssignments(): bool
    {
        return $this->eventTypes()->exists();
    }
    
    /**
     * Check if staff is administrative (no event type assignments)
     * 
     * @return bool
     */
    public function isAdministrative(): bool
    {
        return !$this->is_bookable || !$this->hasEventTypeAssignments();
    }

    // Scope für aktive Mitarbeiter
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Scope für buchbare Mitarbeiter
    public function scopeBookable($query)
    {
        return $query->where('is_bookable', true);
    }

    // Scope für Mitarbeiter eines Unternehmens
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Scope für verfügbare Mitarbeiter (aktiv und buchbar)
    public function scopeAvailable($query)
    {
        return $query->where('active', true)
                     ->where('is_bookable', true);
    }

    // Scope für Mitarbeiter einer Filiale
    public function scopeForBranch($query, $branchId)
    {
        return $query->whereHas('branches', function ($q) use ($branchId) {
            $q->where('branches.id', $branchId);
        });
    }

    // Scope für Mitarbeiter mit bestimmten Services
    public function scopeWithServices($query, $serviceIds)
    {
        if (!is_array($serviceIds)) {
            $serviceIds = [$serviceIds];
        }

        return $query->whereHas('services', function ($q) use ($serviceIds) {
            $q->whereIn('services.id', $serviceIds);
        });
    }

    // Scope für Mitarbeiter mit Terminen in einem Zeitraum
    public function scopeWithAppointmentsInRange($query, $startDate, $endDate)
    {
        return $query->whereHas('appointments', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('starts_at', [$startDate, $endDate]);
        });
    }

    // Scope für Mitarbeiter mit Kalender-Konfiguration
    public function scopeWithCalendarConfig($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('calcom_user_id')
              ->orWhereNotNull('external_calendar_id');
        });
    }

    // Scope für Mitarbeiter ohne Kalender-Konfiguration
    public function scopeWithoutCalendarConfig($query)
    {
        return $query->whereNull('calcom_user_id')
                     ->whereNull('external_calendar_id');
    }

    // Scope mit Anzahl der Termine
    public function scopeWithAppointmentCount($query, $from = null, $to = null)
    {
        return $query->withCount(['appointments' => function ($q) use ($from, $to) {
            if ($from) {
                $q->where('starts_at', '>=', $from);
            }
            if ($to) {
                $q->where('starts_at', '<=', $to);
            }
            $q->where('status', '!=', 'cancelled');
        }]);
    }

    // Scope mit allen wichtigen Beziehungen
    public function scopeWithRelations($query)
    {
        return $query->with([
            'branches:id,name',
            'services:id,name',
            'homeBranch:id,name',
            'eventTypes:id,slug,title'
        ]);
    }

    // Helper-Methode: Prüft ob Mitarbeiter in einer bestimmten Filiale arbeitet
    public function worksInBranch($branchId)
    {
        return $this->branches()->where('branches.id', $branchId)->exists();
    }

    // Helper-Methode: Prüft ob Mitarbeiter einen bestimmten Service anbietet
    public function offersService($serviceId)
    {
        return $this->services()->where('services.id', $serviceId)->exists();
    }

    /**
     * Get the effective calendar configuration for this staff member
     *
     * @return array
     */
    public function getEffectiveCalendar()
    {
        // Wenn der Mitarbeiter seinen eigenen Kalender nutzt
        if ($this->calendar_mode === 'own' && $this->calcom_user_id) {
            return [
                'type' => 'personal',
                'mode' => 'own',
                'user_id' => $this->calcom_user_id,
                'event_type_id' => $this->calcom_event_type_id,
                'link' => $this->calcom_calendar_link,
                'provider' => $this->calendar_provider ?: 'calcom',
            ];
        }
        
        // Wenn der Mitarbeiter einen geteilten Kalender nutzt
        if ($this->calendar_mode === 'shared' && $this->external_calendar_id) {
            return [
                'type' => 'shared',
                'mode' => 'shared',
                'calendar_id' => $this->external_calendar_id,
                'provider' => $this->calendar_provider ?: 'calcom',
            ];
        }
        
        // Standard: Nutze Branch/Company-Kalender
        $branch = $this->homeBranch ?: $this->branch;
        
        if ($branch) {
            $config = $branch->getEffectiveCalcomConfig();
            
            return [
                'type' => 'branch',
                'mode' => 'inherit',
                'config' => $config,
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
            ];
        }
        
        return [
            'type' => 'none',
            'mode' => 'none',
            'message' => 'Keine Kalender-Konfiguration gefunden',
        ];
    }

    /**
     * Check if the staff member is available for bookings
     *
     * @return bool
     */
    public function isBookable(): bool
    {
        return $this->is_bookable && $this->active;
    }

    /**
     * Get a formatted display of the calendar mode
     *
     * @return string
     */
    public function getCalendarModeDisplayAttribute(): string
    {
        return match($this->calendar_mode) {
            'own' => 'Eigener Kalender',
            'shared' => 'Geteilter Kalender',
            'inherit' => 'Filial-Kalender',
            default => 'Nicht konfiguriert',
        };
    }
}
