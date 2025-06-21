<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Scopes\TenantScope;

class Appointment extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'branch_id',
        'staff_id',
        'service_id',
        'calcom_event_type_id',
        'calcom_booking_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'metadata',
        'price',
        'call_id',
        'tenant_id',
        'company_id',
        'external_id',
        'calcom_v2_booking_id',
        'reminder_24h_sent_at',
        'reminder_2h_sent_at',
        'reminder_30m_sent_at',
        'payload',
        'version',
        'lock_expires_at',
        'lock_token'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array',
        'payload' => 'array',
        'reminder_24h_sent_at' => 'datetime',
        'reminder_2h_sent_at' => 'datetime',
        'reminder_30m_sent_at' => 'datetime',
        'lock_expires_at' => 'datetime',
        'version' => 'integer'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        // Increment version on update for optimistic locking
        static::updating(function ($appointment) {
            $appointment->version = ($appointment->version ?? 0) + 1;
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function calcomBooking(): BelongsTo
    {
        return $this->belongsTo(CalcomBooking::class);
    }

    public function calcomEventType(): BelongsTo
    {
        return $this->belongsTo(CalcomEventType::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Scope for upcoming appointments
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())
                     ->where('status', '!=', 'cancelled')
                     ->orderBy('starts_at', 'asc');
    }

    /**
     * Scope for today's appointments
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('starts_at', today())
                     ->orderBy('starts_at', 'asc');
    }

    /**
     * Scope for appointments in a date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        return $query->whereBetween('starts_at', [$start, $end])
                     ->orderBy('starts_at', 'asc');
    }

    /**
     * Scope for appointments by status
     */
    public function scopeByStatus(Builder $query, $status): Builder
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }
        
        return $query->where('status', $status);
    }

    /**
     * Scope for scheduled appointments (not cancelled)
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereIn('status', ['scheduled', 'confirmed']);
    }

    /**
     * Scope for completed appointments
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for appointments by company
     */
    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope for appointments by branch
     */
    public function scopeForBranch(Builder $query, $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for appointments by staff
     */
    public function scopeForStaff(Builder $query, $staffId): Builder
    {
        return $query->where('staff_id', $staffId);
    }

    /**
     * Scope for appointments by customer
     */
    public function scopeForCustomer(Builder $query, $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for appointments with eager loading
     */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with([
            'customer:id,name,email,phone',
            'staff:id,name,email',
            'branch:id,name',
            'service:id,name,duration,price'
        ]);
    }

    /**
     * Scope for appointments that need reminders
     */
    public function scopeNeedingReminders(Builder $query, string $reminderType): Builder
    {
        $now = now();
        
        switch ($reminderType) {
            case '24h':
                return $query->where('starts_at', '>', $now)
                             ->where('starts_at', '<=', $now->copy()->addDay())
                             ->whereNull('reminder_24h_sent_at')
                             ->scheduled();
                             
            case '2h':
                return $query->where('starts_at', '>', $now)
                             ->where('starts_at', '<=', $now->copy()->addHours(2))
                             ->whereNull('reminder_2h_sent_at')
                             ->scheduled();
                             
            case '30m':
                return $query->where('starts_at', '>', $now)
                             ->where('starts_at', '<=', $now->copy()->addMinutes(30))
                             ->whereNull('reminder_30m_sent_at')
                             ->scheduled();
                             
            default:
                return $query;
        }
    }

    /**
     * Scope for overdue appointments (past start time but not completed/cancelled)
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now())
                     ->whereNotIn('status', ['completed', 'cancelled', 'no_show']);
    }
    
    // Temporarily disabled trait methods
    // /**
    //  * Define the loading profiles for this model
    //  */
    // protected static function defineLoadingProfiles(): void
    // {
    //     // Minimal profile - just essential data
    //     static::defineLoadingProfile('minimal', []);
    //     
    //     // Standard profile - common relationships
    //     static::defineLoadingProfile('standard', [
    //         'customer:id,name,email,phone',
    //         'staff:id,name',
    //         'service:id,name,duration,price',
    //     ]);
    //     
    //     // Full profile - all relationships
    //     static::defineLoadingProfile('full', [
    //         'customer',
    //         'staff',
    //         'branch',
    //         'service',
    //         'company',
    //         'calcomBooking',
    //         'calcomEventType',
    //     ]);
    //     
    //     // Counts profile - for listing with counts
    //     static::defineLoadingProfile('counts', []);
    // }
    // 
    // /**
    //  * Get allowed includes for API
    //  */
    // protected function getAllowedIncludes(): array
    // {
    //     return [
    //         'customer',
    //         'staff',
    //         'branch',
    //         'service',
    //         'company',
    //         'calcomBooking',
    //     ];
    // }
    // 
    // /**
    //  * Get countable relations
    //  */
    // protected function getCountableRelations(): array
    // {
    //     // No countable relations for appointments
    //     return [];
    // }
    
}
