<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Scopes\TenantScope;

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
        'metadata',
        // Neue Felder
        'minimum_booking_notice',
        'booking_future_limit',
        'time_slot_interval',
        'buffer_before',
        'buffer_after',
        'locations',
        'custom_fields',
        'max_bookings_per_day',
        'seats_per_time_slot',
        'schedule_id',
        'recurring_config',
        'setup_status',
        'setup_checklist',
        'webhook_settings',
        'calcom_url'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
        'locations' => 'array',
        'custom_fields' => 'array',
        'recurring_config' => 'array',
        'setup_checklist' => 'array',
        'webhook_settings' => 'array',
        'is_team_event' => 'boolean',
        'requires_confirmation' => 'boolean'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

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
    
    /**
     * Check if event type is fully configured
     */
    public function isFullyConfigured(): bool
    {
        return $this->setup_status === 'complete';
    }
    
    /**
     * Get setup progress percentage
     */
    public function getSetupProgress(): int
    {
        $checklist = $this->setup_checklist ?? [];
        if (empty($checklist)) {
            return 0;
        }
        
        $completed = collect($checklist)->filter(fn($item) => $item['completed'] ?? false)->count();
        $total = count($checklist);
        
        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }
    
    /**
     * Update setup checklist item
     */
    public function updateChecklistItem(string $key, bool $completed, ?string $note = null): void
    {
        $checklist = $this->setup_checklist ?? [];
        
        $checklist[$key] = [
            'completed' => $completed,
            'updated_at' => now()->toIso8601String(),
            'note' => $note
        ];
        
        $this->setup_checklist = $checklist;
        $this->updateSetupStatus();
        $this->save();
    }
    
    /**
     * Update setup status based on checklist
     */
    protected function updateSetupStatus(): void
    {
        $progress = $this->getSetupProgress();
        
        if ($progress === 0) {
            $this->setup_status = 'incomplete';
        } elseif ($progress === 100) {
            $this->setup_status = 'complete';
        } else {
            $this->setup_status = 'partial';
        }
    }
    
    /**
     * Get Cal.com direct edit URL
     */
    public function getCalcomEditUrl(): ?string
    {
        if (!$this->calcom_numeric_event_type_id) {
            return null;
        }
        
        $baseUrl = config('services.calcom.app_url', 'https://app.cal.com');
        return "{$baseUrl}/event-types/{$this->calcom_numeric_event_type_id}";
    }
    
    /**
     * Get setup checklist
     */
    public function getSetupChecklist(): array
    {
        if (empty($this->setup_checklist)) {
            $this->initializeChecklist();
        }
        
        return $this->setup_checklist ?? [];
    }
    
    /**
     * Initialize setup checklist
     */
    public function initializeChecklist(): void
    {
        $this->setup_checklist = [
            'basic_info' => [
                'label' => 'Basis-Informationen',
                'completed' => !empty($this->name) && !empty($this->duration_minutes),
                'syncable' => true
            ],
            'availability' => [
                'label' => 'Verfügbarkeiten',
                'completed' => !empty($this->schedule_id),
                'syncable' => false,
                'calcom_section' => 'availability'
            ],
            'booking_settings' => [
                'label' => 'Buchungseinstellungen',
                'completed' => $this->minimum_booking_notice !== null,
                'syncable' => true
            ],
            'locations' => [
                'label' => 'Standorte/Orte',
                'completed' => !empty($this->locations),
                'syncable' => true
            ],
            'custom_fields' => [
                'label' => 'Benutzerdefinierte Felder',
                'completed' => false,
                'syncable' => false,
                'calcom_section' => 'advanced'
            ],
            'notifications' => [
                'label' => 'Benachrichtigungen',
                'completed' => false,
                'syncable' => false,
                'calcom_section' => 'workflows'
            ]
        ];
        
        $this->updateSetupStatus();
        $this->save();
    }
}
