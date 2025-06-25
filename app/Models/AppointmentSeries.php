<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\TenantScope;

class AppointmentSeries extends Model
{
    use HasFactory;

    protected $table = 'appointment_series';

    protected $fillable = [
        'series_id',
        'company_id',
        'customer_id',
        'branch_id',
        'staff_id',
        'service_id',
        'recurrence_type',
        'recurrence_pattern',
        'recurrence_interval',
        'series_start_date',
        'series_end_date',
        'occurrences_count',
        'appointment_time',
        'duration_minutes',
        'total_appointments',
        'completed_appointments',
        'cancelled_appointments',
        'status',
        'exceptions',
        'modifications',
        'price_per_session',
        'total_price',
        'auto_confirm',
        'send_reminders',
        'metadata',
        'notes',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'recurrence_pattern' => 'array',
        'series_start_date' => 'date',
        'series_end_date' => 'date',
        'exceptions' => 'array',
        'modifications' => 'array',
        'metadata' => 'array',
        'auto_confirm' => 'boolean',
        'send_reminders' => 'boolean',
        'cancelled_at' => 'datetime',
        'appointment_time' => 'datetime:H:i',
        'price_per_session' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the staff member
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the service
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get all appointments in this series
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'series_id', 'series_id');
    }

    /**
     * Get active appointments in this series
     */
    public function activeAppointments(): HasMany
    {
        return $this->appointments()->whereIn('status', ['scheduled', 'confirmed']);
    }

    /**
     * Get upcoming appointments in this series
     */
    public function upcomingAppointments(): HasMany
    {
        return $this->appointments()
            ->where('starts_at', '>', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('starts_at');
    }

    /**
     * Get completed appointments in this series
     */
    public function completedAppointments(): HasMany
    {
        return $this->appointments()->where('status', 'completed');
    }

    /**
     * Get cancelled appointments in this series
     */
    public function cancelledAppointments(): HasMany
    {
        return $this->appointments()->where('status', 'cancelled');
    }

    /**
     * Check if series is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->series_end_date || $this->series_end_date->isFuture());
    }

    /**
     * Check if series has upcoming appointments
     */
    public function hasUpcomingAppointments(): bool
    {
        return $this->upcomingAppointments()->exists();
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_appointments == 0) {
            return 0;
        }

        $completed = $this->completed_appointments + $this->cancelled_appointments;
        return round(($completed / $this->total_appointments) * 100, 2);
    }

    /**
     * Get human-readable recurrence description
     */
    public function getRecurrenceDescription(): string
    {
        $descriptions = [
            'daily' => 'Täglich',
            'weekly' => 'Wöchentlich',
            'biweekly' => 'Alle 2 Wochen',
            'monthly' => 'Monatlich',
            'custom' => 'Benutzerdefiniert'
        ];

        $base = $descriptions[$this->recurrence_type] ?? $this->recurrence_type;

        if ($this->recurrence_interval > 1) {
            $base = "Alle {$this->recurrence_interval} " . $this->getIntervalUnit();
        }

        // Add day information for weekly/biweekly
        if (in_array($this->recurrence_type, ['weekly', 'biweekly']) && 
            isset($this->recurrence_pattern['days_of_week'])) {
            $days = $this->getDayNames($this->recurrence_pattern['days_of_week']);
            $base .= ' am ' . implode(', ', $days);
        }

        return $base;
    }

    /**
     * Get interval unit in German
     */
    protected function getIntervalUnit(): string
    {
        $units = [
            'daily' => 'Tage',
            'weekly' => 'Wochen',
            'biweekly' => 'Wochen',
            'monthly' => 'Monate'
        ];

        return $units[$this->recurrence_type] ?? '';
    }

    /**
     * Get day names from day numbers
     */
    protected function getDayNames(array $dayNumbers): array
    {
        $dayNames = [
            0 => 'Sonntag',
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag'
        ];

        return array_map(function($dayNumber) use ($dayNames) {
            return $dayNames[$dayNumber] ?? $dayNumber;
        }, $dayNumbers);
    }

    /**
     * Check if a specific date is excluded
     */
    public function isDateExcluded(\Carbon\Carbon $date): bool
    {
        if (empty($this->exceptions)) {
            return false;
        }

        $dateString = $date->format('Y-m-d');
        return in_array($dateString, $this->exceptions);
    }

    /**
     * Add exception date
     */
    public function addException(\Carbon\Carbon $date): void
    {
        $exceptions = $this->exceptions ?? [];
        $dateString = $date->format('Y-m-d');
        
        if (!in_array($dateString, $exceptions)) {
            $exceptions[] = $dateString;
            $this->update(['exceptions' => $exceptions]);
        }
    }

    /**
     * Remove exception date
     */
    public function removeException(\Carbon\Carbon $date): void
    {
        $exceptions = $this->exceptions ?? [];
        $dateString = $date->format('Y-m-d');
        
        $exceptions = array_values(array_diff($exceptions, [$dateString]));
        $this->update(['exceptions' => $exceptions]);
    }

    /**
     * Get modification for specific date
     */
    public function getModificationForDate(\Carbon\Carbon $date): ?array
    {
        if (empty($this->modifications)) {
            return null;
        }

        $dateString = $date->format('Y-m-d');
        return $this->modifications[$dateString] ?? null;
    }

    /**
     * Calculate total revenue
     */
    public function calculateTotalRevenue(): float
    {
        return $this->appointments()
            ->where('status', 'completed')
            ->sum('price');
    }

    /**
     * Calculate expected revenue
     */
    public function calculateExpectedRevenue(): float
    {
        if ($this->total_price) {
            return $this->total_price;
        }

        return ($this->price_per_session ?? 0) * $this->total_appointments;
    }

    /**
     * Get completion rate
     */
    public function getCompletionRate(): float
    {
        $scheduled = $this->total_appointments - $this->cancelled_appointments;
        if ($scheduled == 0) {
            return 0;
        }

        return round(($this->completed_appointments / $scheduled) * 100, 2);
    }
}