<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\TenantScope;

class Service extends Model
{
    use SoftDeletes, HasFactory;

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'company_id',
        'branch_id',
        'tenant_id',
        'name',
        'description',
        'price',
        'default_duration_minutes',
        'active',
        'category',
        'sort_order',
        'min_staff_required',
        'max_bookings_per_day',
        'buffer_time_minutes',
        'is_online_bookable',
        'calcom_event_type_id',
        'duration'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
        'is_online_bookable' => 'boolean',
        'default_duration_minutes' => 'integer',
        'min_staff_required' => 'integer',
        'max_bookings_per_day' => 'integer',
        'buffer_time_minutes' => 'integer',
        'sort_order' => 'integer',
        'duration' => 'integer'
    ];

    protected $attributes = [
        'active' => true,
        'default_duration_minutes' => 30,
        'is_online_bookable' => true,
        'min_staff_required' => 1,
        'buffer_time_minutes' => 0
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    // Getter für duration (Rückwärtskompatibilität)
    public function getDurationAttribute($value)
    {
        return $value ?? $this->default_duration_minutes;
    }

    public function setDurationAttribute($value)
    {
        $this->attributes['duration'] = $value;
        $this->attributes['default_duration_minutes'] = $value;
    }

    // Relationen
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'service_staff')
            ->withPivot('duration_minutes', 'price', 'active')
            ->withTimestamps();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Service-Override/Vererbung: Effektive EventType-Id
    public function getEffectiveEventTypeId()
    {
        if (!empty($this->calcom_event_type_id)) {
            return $this->calcom_event_type_id; // Override durch Service
        }
        // Fallback auf Company
        return $this->company?->calcom_event_type_id;
    }

    public function inheritsFromCompany()
    {
        return empty($this->calcom_event_type_id);
    }
}
