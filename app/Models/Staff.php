<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Traits\HasConfigurationInheritance;

class Staff extends Model
{
    use HasFactory, SoftDeletes, HasConfigurationInheritance, BelongsToCompany;
    protected $table = 'staff';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'branch_id',
        'name',
        'email',
        'phone',
        'position',
        'department',
        'status',
        'hire_date',
        'skills',
        'specializations',
        'availability',
        'working_hours',
        'break_times',
        'vacation_dates',
        'is_available',
        'is_active',
        'is_bookable',
        'can_book_appointments',
        'appointment_duration_minutes',
        'buffer_time_minutes',
        'max_appointments_per_day',
        'calcom_user_id',
        'external_calendar_id',
        'color_code',
        'sort_order',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'skills' => 'array',
        'specializations' => 'array',
        'availability' => 'array',
        'working_hours' => 'array',
        'break_times' => 'array',
        'vacation_dates' => 'array',
        'metadata' => 'array',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
        'can_book_appointments' => 'boolean',
        'hire_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function callbackRequests(): HasMany
    {
        return $this->hasMany(CallbackRequest::class, 'assigned_to');
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_staff')
            ->withPivot([
                'is_primary',
                'can_book',
                'custom_price',
                'custom_duration_minutes',
                'commission_rate',
                'specialization_notes',
                'allowed_segments',
                'skill_level',
                'weight',
                'is_active',
                'assigned_at'
            ])
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->orderByPivot('is_primary', 'desc')
            ->orderBy('name');
    }

    public function primaryServices(): BelongsToMany
    {
        return $this->services()->wherePivot('is_primary', true);
    }

    public function bookableServices(): BelongsToMany
    {
        return $this->services()->wherePivot('can_book', true);
    }

    /**
     * Cal.com host mappings for this staff member (Phase 2: Staff Assignment)
     */
    public function calcomHostMappings(): HasMany
    {
        return $this->hasMany(CalcomHostMapping::class);
    }

    /**
     * Get upcoming scheduled/confirmed appointments for this staff member
     * Used for staff scheduling, resource planning, and workload management
     * Performance: More efficient than filtering appointments() every time
     */
    public function upcomingAppointments(): HasMany
    {
        return $this->appointments()
            ->where('starts_at', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('starts_at', 'asc');
    }

    /**
     * Get completed appointments for this staff member (historical record)
     * Used for performance metrics, productivity tracking, and revenue analysis
     * Performance: Specialized query for completed appointments only
     */
    public function completedAppointments(): HasMany
    {
        return $this->appointments()
            ->where('status', 'completed')
            ->orderBy('starts_at', 'desc');
    }
}