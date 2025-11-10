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
        'has_appointment' => 'boolean',  // ✅ FIXED: was appointment_made
        'first_visit' => 'boolean',
        'customer_name_verified' => 'boolean',
        'verification_confidence' => 'decimal:2',
        'no_show_count' => 'integer',
        'reschedule_count' => 'integer',
        'calculated_cost' => 'integer',  // ✅ FIXED: was cost - actual DB column
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
        'started_at' => 'datetime',  // ✅ Added - exists in DB
        'ended_at' => 'datetime',    // ✅ Added - exists in DB
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
        try {
            // Load latest appointment if not already loaded
            if (!$this->relationLoaded('latestAppointment')) {
                $this->load('latestAppointment');
            }

            $latest = $this->latestAppointment;

            if ($latest) {
                return $latest;
            }
        } catch (\Exception $e) {
            // Silently handle missing call_id foreign key from DB backup
            // The call_id column doesn't exist in appointments table from Sept 21 backup
        }

        try {
            // Fallback to legacy converted appointment
            if (!$this->relationLoaded('convertedAppointment')) {
                $this->load('convertedAppointment');
            }

            return $this->convertedAppointment;
        } catch (\Exception $e) {
            // Silently handle missing converted_appointment_id foreign key from DB backup
            // The converted_appointment_id column doesn't exist in calls table from Sept 21 backup
            return null;
        }
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
     * ✅ FIXED: uses has_appointment (actual DB column)
     */
    public function scopeWithAppointment($query)
    {
        return $query->where('has_appointment', true);
    }

    /**
     * Scope: Successful calls
     * ✅ FIXED: uses status = 'completed' (actual DB column)
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Failed calls
     * ✅ FIXED: uses status = 'failed' (actual DB column)
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
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
     *
     * Finds calls that are still marked as active/ongoing but haven't been
     * updated in X hours, indicating the call_ended webhook was missed.
     */
    public function scopeStuck($query, int $hours = 2)
    {
        return $query->whereIn('status', ['ongoing', 'in_progress', 'in-progress', 'active'])
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
     * ⚠️ WARNING: session_outcome stored in metadata JSON, not as column
     * This scope cannot efficiently query JSON field - consider refactoring
     */
    public function scopeWithOutcome($query, ?string $outcome = null)
    {
        // Note: session_outcome is in metadata JSON, not a direct column
        // This query will be inefficient but maintains backwards compatibility
        if ($outcome) {
            $query->whereRaw("JSON_EXTRACT(metadata, '$.session_outcome') = ?", [$outcome]);
        } else {
            $query->whereRaw("JSON_EXTRACT(metadata, '$.session_outcome') IS NOT NULL");
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
     * Get appointment revenue (calculated from service prices)
     *
     * ✅ FIX PERF-001: Uses eager loaded appointments when available to prevent N+1
     * @return int Revenue in cents (EUR)
     */
    public function getAppointmentRevenue(): int
    {
        // ✅ Use relationship data if loaded (prevents N+1 queries)
        if ($this->relationLoaded('appointments')) {
            $revenue = $this->appointments
                ->filter(fn($appointment) => $appointment->relationLoaded('service'))
                ->sum(fn($appointment) => $appointment->service->price ?? 0);
            return (int)($revenue * 100);
        }

        // Fallback to query if not eager loaded - join with services to get price
        return (int)($this->appointments()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->sum('services.price') * 100); // Convert EUR to cents
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

    /*
    |--------------------------------------------------------------------------
    | Accessors for Livewire Serialization (2025-10-22)
    |--------------------------------------------------------------------------
    | These accessors replace closures in RelationManagers to fix Livewire
    | serialization issues. Closures are not JSON-serializable and cause
    | "Snapshot missing on Livewire component" errors.
    */

    /**
     * Get formatted duration string
     *
     * @return string|null
     */
    public function getDurationFormattedAttribute(): ?string
    {
        if (!$this->duration_sec) {
            return null;
        }

        $minutes = floor($this->duration_sec / 60);
        $seconds = $this->duration_sec % 60;

        return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
    }

    /**
     * Get appointment status information (tooltip, color, icon)
     * ✅ FIXED: uses has_appointment (actual DB column)
     *
     * @return array{tooltip: string, color: string, icon: string}
     */
    public function getAppointmentStatusInfoAttribute(): array
    {
        if ($this->has_appointment && !$this->converted_appointment_id) {
            return [
                'tooltip' => '⚠️ Buchung fehlgeschlagen - Termin wurde nicht erstellt',
                'color' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
            ];
        }

        if ($this->has_appointment && $this->converted_appointment_id) {
            return [
                'tooltip' => '✅ Termin erfolgreich gebucht (ID: ' . $this->converted_appointment_id . ')',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ];
        }

        return [
            'tooltip' => 'Kein Termin gebucht',
            'color' => 'gray',
            'icon' => 'heroicon-o-calendar',
        ];
    }

    /**
     * Check if call has recording URL
     *
     * @return bool
     */
    public function getHasRecordingAttribute(): bool
    {
        return !empty($this->recording_url);
    }

    /**
     * Get booking status text
     *
     * @return string
     */
    public function getBookingStatusAttribute(): string
    {
        if ($this->has_appointment && !$this->converted_appointment_id) {
            return 'Fehlgeschlagen';
        }
        if ($this->has_appointment && $this->converted_appointment_id) {
            return 'Erfolgreich';
        }
        if ($this->has_appointment === 0) {
            return 'Nicht versucht';
        }
        return '-';
    }

    /*
    |--------------------------------------------------------------------------
    | Backwards Compatibility Accessors (2025-10-27)
    |--------------------------------------------------------------------------
    | Maps old column names to actual database columns for Sept 21 backup
    */

    /**
     * Accessor: call_successful → status mapping
     * Database has 'status', code expects 'call_successful'
     */
    public function getCallSuccessfulAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Accessor: appointment_made → has_appointment mapping
     * Database has 'has_appointment', code expects 'appointment_made'
     */
    public function getAppointmentMadeAttribute(): bool
    {
        return $this->has_appointment ?? false;
    }

    /**
     * Accessor: cost → calculated_cost mapping
     * Database has 'calculated_cost', code expects 'cost'
     */
    public function getCostAttribute(): ?float
    {
        return $this->calculated_cost ? $this->calculated_cost / 100 : null;
    }

    /**
     * Accessor: customer_name from metadata or relationship
     * No database column, extracted from metadata JSON or customer relationship
     */
    public function getCustomerNameAttribute(): ?string
    {
        // Try metadata first
        if ($this->metadata && isset($this->metadata['customer_name'])) {
            return $this->metadata['customer_name'];
        }

        // Fallback to customer relationship
        if ($this->relationLoaded('customer') && $this->customer) {
            return $this->customer->name;
        }

        return null;
    }

    /**
     * Accessor: sentiment from metadata
     * No database column, extracted from metadata JSON
     */
    public function getSentimentAttribute(): ?string
    {
        if ($this->metadata && isset($this->metadata['sentiment'])) {
            return $this->metadata['sentiment'];
        }

        return null;
    }

    /**
     * Accessor: session_outcome from metadata
     * No database column, extracted from metadata JSON
     */
    public function getSessionOutcomeAttribute(): ?string
    {
        if ($this->metadata && isset($this->metadata['session_outcome'])) {
            return $this->metadata['session_outcome'];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | System Duration Calculations (2025-11-06)
    |--------------------------------------------------------------------------
    | Real-time durations between system transitions for performance analysis
    */

    /**
     * Get duration from call start to customer linking (seconds)
     * Measures: Retell → Laravel customer identification
     *
     * @return int|null Duration in seconds (null if unrealistic or invalid)
     */
    public function getTimeToCustomerLinkSeconds(): ?int
    {
        if (!$this->customer_linked_at || !$this->created_at) {
            return null;
        }

        $seconds = $this->created_at->diffInSeconds($this->customer_linked_at);

        // Only return if positive and realistic (< 24 hours)
        // customer_linked_at might be from a batch update, not real-time
        if ($seconds < 0 || $seconds > 86400) {
            return null;
        }

        return $seconds;
    }

    /**
     * Get duration from customer link to appointment creation (seconds)
     * Measures: Laravel → Cal.com booking creation
     *
     * @return int|null Duration in seconds (null if unrealistic or invalid)
     */
    public function getTimeFromCustomerToAppointmentSeconds(): ?int
    {
        if (!$this->customer_linked_at) {
            return null;
        }

        // Use relationLoaded to prevent N+1
        if ($this->relationLoaded('appointments') && $this->appointments->isNotEmpty()) {
            $firstAppointment = $this->appointments->first();
            $seconds = $this->customer_linked_at->diffInSeconds($firstAppointment->created_at, false);

            // Only return if realistic (< 1 hour and positive)
            if ($seconds < 0 || $seconds > 3600) {
                return null;
            }

            return $seconds;
        }

        // Fallback query
        $appointment = $this->appointments()->orderBy('created_at')->first();
        if ($appointment) {
            $seconds = $this->customer_linked_at->diffInSeconds($appointment->created_at, false);

            if ($seconds < 0 || $seconds > 3600) {
                return null;
            }

            return $seconds;
        }

        return null;
    }

    /**
     * Get total duration from call start to appointment creation (seconds)
     * Measures: Complete flow Retell → Laravel → Cal.com
     *
     * @return int|null Duration in seconds (null if unrealistic or invalid)
     */
    public function getTotalTimeToAppointmentSeconds(): ?int
    {
        if (!$this->created_at) {
            return null;
        }

        // Use relationLoaded to prevent N+1
        if ($this->relationLoaded('appointments') && $this->appointments->isNotEmpty()) {
            $firstAppointment = $this->appointments->first();
            $seconds = $this->created_at->diffInSeconds($firstAppointment->created_at, false);

            // Only return if realistic (< 2 hours and positive)
            if ($seconds < 0 || $seconds > 7200) {
                return null;
            }

            return $seconds;
        }

        // Fallback query
        $appointment = $this->appointments()->orderBy('created_at')->first();
        if ($appointment) {
            $seconds = $this->created_at->diffInSeconds($appointment->created_at, false);

            if ($seconds < 0 || $seconds > 7200) {
                return null;
            }

            return $seconds;
        }

        return null;
    }

    /**
     * Get formatted duration breakdown for display
     * Returns HTML string with system-to-system durations
     *
     * @return string|null HTML formatted duration breakdown
     */
    public function getSystemDurationBreakdownAttribute(): ?string
    {
        $parts = [];

        // Call duration
        if ($this->duration_sec) {
            $parts[] = sprintf(
                '<span class="text-gray-600">📞 Anrufdauer:</span> <span class="font-semibold">%s</span>',
                $this->formatDuration($this->duration_sec)
            );
        }

        // Time to customer link
        $timeToCustomer = $this->getTimeToCustomerLinkSeconds();
        if ($timeToCustomer !== null) {
            $parts[] = sprintf(
                '<span class="text-blue-600">🔍 Retell → Customer:</span> <span class="font-semibold">%s</span>',
                $this->formatDuration($timeToCustomer)
            );
        }

        // Time from customer to appointment
        $timeToAppointment = $this->getTimeFromCustomerToAppointmentSeconds();
        if ($timeToAppointment !== null) {
            $parts[] = sprintf(
                '<span class="text-green-600">📅 Customer → Termin:</span> <span class="font-semibold">%s</span>',
                $this->formatDuration($timeToAppointment)
            );
        }

        // Total time to appointment
        $totalTime = $this->getTotalTimeToAppointmentSeconds();
        if ($totalTime !== null) {
            $parts[] = sprintf(
                '<span class="text-purple-600">⚡ Gesamt (Call → Termin):</span> <span class="font-semibold">%s</span>',
                $this->formatDuration($totalTime)
            );
        }

        return !empty($parts) ? implode('<br>', $parts) : null;
    }

    /**
     * Format seconds into human-readable duration
     *
     * @param int $seconds
     * @return string
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0
                ? "{$minutes}m {$remainingSeconds}s"
                : "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? "{$hours}h {$remainingMinutes}m"
            : "{$hours}h";
    }
}