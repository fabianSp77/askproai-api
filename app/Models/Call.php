<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * Mass Assignment Protection
     *
     * Using $guarded to protect only critical fields while allowing flexibility
     * for legitimate webhook updates. All fields below must NEVER be mass-assigned
     * as they represent security-critical or calculated values.
     *
     * SECURITY: Protects against VULN-009 - Mass Assignment vulnerability
     */
    protected $guarded = [
        'id',                         // Primary key

        // Multi-tenant isolation (CRITICAL)
        'company_id',                 // Must be set only via phoneNumber relationship
        'branch_id',                  // Must be set only via phoneNumber relationship

        // Financial data (CRITICAL - calculated by CostCalculator)
        'cost',                       // Calculated field
        'cost_cents',                 // Calculated field
        'base_cost',                  // Calculated field
        'reseller_cost',              // Calculated field
        'customer_cost',              // Calculated field
        'platform_profit',            // Calculated field
        'reseller_profit',            // Calculated field
        'total_profit',               // Calculated field
        'profit_margin_platform',     // Calculated field
        'profit_margin_reseller',     // Calculated field
        'profit_margin_total',        // Calculated field
        'retell_cost',                // Calculated field
        'cost_calculation_method',    // Set by CostCalculator
        'cost_breakdown',             // Set by CostCalculator

        // System timestamps
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'analysis' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'action_items' => 'array',
        'summary_translations' => 'array',
        'custom_analysis_data' => 'array',
        'customer_data_backup' => 'array',
        'llm_token_usage' => 'array',
        'linking_metadata' => 'array',
        'consent_given' => 'boolean',
        'data_forwarded' => 'boolean',
        'data_validation_completed' => 'boolean',
        'call_successful' => 'boolean',
        'appointment_made' => 'boolean',
        'first_visit' => 'boolean',
        'customer_name_verified' => 'boolean',
        'verification_confidence' => 'decimal:2',
        'no_show_count' => 'integer',
        'reschedule_count' => 'integer',
        'cost' => 'decimal:2',
        'cost_cents' => 'integer',
        'base_cost' => 'integer',
        'reseller_cost' => 'integer',
        'customer_cost' => 'integer',
        'platform_profit' => 'integer',
        'reseller_profit' => 'integer',
        'total_profit' => 'integer',
        'profit_margin_platform' => 'decimal:2',
        'profit_margin_reseller' => 'decimal:2',
        'profit_margin_total' => 'decimal:2',
        'retell_cost' => 'decimal:2',
        'cost_breakdown' => 'array',
        'sentiment_score' => 'float',
        'consent_at' => 'datetime',
        'forwarded_at' => 'datetime',
        'customer_data_collected_at' => 'datetime',
        'customer_linked_at' => 'datetime',
        'appointment_linked_at' => 'datetime',
        'customer_link_confidence' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(RetellAgent::class, 'agent_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Direct appointment link (via appointment_id foreign key)
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * NEW PRIMARY: All appointments originated from this call
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'call_id');
    }

    /**
     * NEW HELPER: Latest/primary appointment for this call
     */
    public function latestAppointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'call_id')
            ->latestOfMany('created_at');
    }

    /**
     * LEGACY: Backwards compatibility for converted appointments
     */
    public function convertedAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'converted_appointment_id');
    }

    /**
     * ⏰ NEW: Appointment wishes for this call (unfulfilled requests)
     */
    public function appointmentWishes(): HasMany
    {
        return $this->hasMany(\App\Models\AppointmentWish::class, 'call_id');
    }

    /**
     * SMART ACCESSOR: Unified appointment access
     * Priority: Latest call_id appointment > converted appointment
     */
    public function getAppointmentAttribute(): ?Appointment
    {
        // Load latest appointment if not already loaded
        if (!$this->relationLoaded('latestAppointment')) {
            $this->load('latestAppointment');
        }

        $latest = $this->latestAppointment;

        if ($latest) {
            return $latest;
        }

        // Fallback to legacy converted appointment
        if (!$this->relationLoaded('convertedAppointment')) {
            $this->load('convertedAppointment');
        }

        return $this->convertedAppointment;
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes (Phase 4)
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Calls from today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Calls within date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Recent calls (last N hours)
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Calls with customer linked
     */
    public function scopeWithCustomer($query)
    {
        return $query->whereNotNull('customer_id');
    }

    /**
     * Scope: Calls without customer
     */
    public function scopeWithoutCustomer($query)
    {
        return $query->whereNull('customer_id');
    }

    /**
     * Scope: Calls with transcript
     */
    public function scopeWithTranscript($query)
    {
        return $query->whereNotNull('transcript')
            ->where('transcript', '!=', '');
    }

    /**
     * Scope: Calls with appointments made
     */
    public function scopeWithAppointment($query)
    {
        return $query->where('appointment_made', true);
    }

    /**
     * Scope: Successful calls
     */
    public function scopeSuccessful($query)
    {
        return $query->where('call_successful', true);
    }

    /**
     * Scope: Failed calls
     */
    public function scopeFailed($query)
    {
        return $query->where('call_successful', false);
    }

    /**
     * Scope: Ongoing/active calls
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['ongoing', 'in-progress', 'active']);
    }

    /**
     * Scope: Stuck calls (active for >2 hours)
     */
    public function scopeStuck($query, int $hours = 2)
    {
        return $query->whereIn('status', ['ongoing', 'in-progress', 'active'])
            ->where('created_at', '<', now()->subHours($hours));
    }

    /**
     * Scope: Calls with specific link status
     */
    public function scopeWithLinkStatus($query, string $status)
    {
        return $query->where('customer_link_status', $status);
    }

    /**
     * Scope: Calls that are linked to customers
     */
    public function scopeLinked($query)
    {
        return $query->where('customer_link_status', 'linked');
    }

    /**
     * Scope: Calls with name only (not fully linked)
     */
    public function scopeNameOnly($query)
    {
        return $query->where('customer_link_status', 'name_only');
    }

    /**
     * Scope: Anonymous calls
     */
    public function scopeAnonymous($query)
    {
        return $query->where('customer_link_status', 'anonymous');
    }

    /**
     * Scope: Calls by company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Calls by agent
     */
    public function scopeForAgent($query, string $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope: Calls with duration
     */
    public function scopeWithDuration($query)
    {
        return $query->where('duration_sec', '>', 0);
    }

    /**
     * Scope: Calls with session outcome
     */
    public function scopeWithOutcome($query, ?string $outcome = null)
    {
        $query = $query->whereNotNull('session_outcome');

        if ($outcome) {
            $query->where('session_outcome', $outcome);
        }

        return $query;
    }

    /**
     * Scope: Calls with booking confirmed
     */
    public function scopeBookingConfirmed($query)
    {
        return $query->where('booking_confirmed', true);
    }

    /**
     * Scope: Calls by Retell call ID
     */
    public function scopeByRetellId($query, string $retellCallId)
    {
        return $query->where('retell_call_id', $retellCallId);
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue & Profit Calculations
    |--------------------------------------------------------------------------
    */

    /**
     * Get appointment revenue (only paid appointments)
     *
     * ✅ FIX PERF-001: Uses eager loaded appointments when available to prevent N+1
     * @return int Revenue in cents (EUR)
     */
    public function getAppointmentRevenue(): int
    {
        // ✅ Use relationship data if loaded (prevents N+1 queries)
        if ($this->relationLoaded('appointments')) {
            return (int)($this->appointments->where('price', '>', 0)->sum('price') * 100);
        }

        // Fallback to query if not eager loaded
        return $this->appointments()
            ->where('price', '>', 0)
            ->sum('price') * 100; // Convert EUR to cents
    }

    /**
     * Get call profit (revenue - costs)
     *
     * ✅ FIX PERF-001: Optimized with eager loaded appointments
     * @return int Profit in cents
     */
    public function getCallProfit(): int
    {
        $revenue = $this->getAppointmentRevenue(); // Now optimized with relationLoaded() check
        $cost = $this->base_cost ?? 0;

        return $revenue - $cost;
    }

    /**
     * Check if call generated revenue
     *
     * @return bool
     */
    public function hasRevenue(): bool
    {
        return $this->getAppointmentRevenue() > 0;
    }

    /**
     * Check if call was profitable
     *
     * @return bool
     */
    public function isProfitable(): bool
    {
        return $this->getCallProfit() > 0;
    }
}