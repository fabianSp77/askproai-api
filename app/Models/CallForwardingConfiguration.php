<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CallForwardingConfiguration Model
 *
 * ✅ Phase 2: Branch-level call forwarding configuration for intelligent call routing
 *
 * Features:
 * - Trigger-based forwarding (no_availability, after_hours, booking_failed, etc.)
 * - Priority-based rule evaluation (lowest priority number = highest priority)
 * - Active hours scheduling (when forwarding is enabled)
 * - Multi-tenant isolation via BelongsToCompany trait
 * - Soft deletes for audit trail
 *
 * Example forwarding_rules JSON:
 * ```json
 * [
 *   {
 *     "trigger": "no_availability",
 *     "target_number": "+4915112345678",
 *     "priority": 1,
 *     "conditions": {"time_window": "all"}
 *   },
 *   {
 *     "trigger": "after_hours",
 *     "target_number": "+4915187654321",
 *     "priority": 2,
 *     "conditions": {"outside_business_hours": true}
 *   }
 * ]
 * ```
 *
 * @property int $id
 * @property int $company_id
 * @property string $branch_id UUID
 * @property array $forwarding_rules
 * @property string|null $default_forwarding_number
 * @property string|null $emergency_forwarding_number
 * @property array|null $active_hours
 * @property string $timezone
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CallForwardingConfiguration extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * Forwarding trigger types
     */
    public const TRIGGER_NO_AVAILABILITY = 'no_availability';
    public const TRIGGER_AFTER_HOURS = 'after_hours';
    public const TRIGGER_BOOKING_FAILED = 'booking_failed';
    public const TRIGGER_HIGH_CALL_VOLUME = 'high_call_volume';
    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGERS = [
        self::TRIGGER_NO_AVAILABILITY,
        self::TRIGGER_AFTER_HOURS,
        self::TRIGGER_BOOKING_FAILED,
        self::TRIGGER_HIGH_CALL_VOLUME,
        self::TRIGGER_MANUAL,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'forwarding_rules',
        'default_forwarding_number',
        'emergency_forwarding_number',
        'active_hours',
        'timezone',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'forwarding_rules' => 'array',
        'active_hours' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the branch this configuration belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if call should be forwarded for given trigger
     *
     * Evaluates forwarding rules based on:
     * - Trigger type match
     * - Configuration is active
     * - Current time within active hours (if configured)
     * - Rule-specific conditions
     *
     * @param string $trigger Trigger type (no_availability, after_hours, etc.)
     * @param array $context Additional context for condition evaluation
     * @return bool
     */
    public function shouldForward(string $trigger, array $context = []): bool
    {
        // Configuration must be active
        if (!$this->is_active) {
            return false;
        }

        // Check if within active hours
        if ($this->active_hours && !$this->isWithinActiveHours()) {
            return false;
        }

        // Find matching rule for trigger
        $rule = $this->getRuleForTrigger($trigger);
        if (!$rule) {
            return false;
        }

        // Evaluate rule conditions if present
        if (isset($rule['conditions'])) {
            return $this->evaluateConditions($rule['conditions'], $context);
        }

        return true;
    }

    /**
     * Get target phone number for specific trigger
     *
     * Returns the target number from matching rule, or default fallback
     *
     * Priority:
     * 1. Matching rule target_number
     * 2. default_forwarding_number
     * 3. emergency_forwarding_number (as last resort)
     *
     * @param string $trigger Trigger type
     * @return string|null Target phone number or null if no forwarding configured
     */
    public function getTargetNumber(string $trigger): ?string
    {
        $rule = $this->getRuleForTrigger($trigger);

        if ($rule && isset($rule['target_number'])) {
            return $rule['target_number'];
        }

        return $this->default_forwarding_number ?? $this->emergency_forwarding_number;
    }

    /**
     * Get rule for specific trigger
     *
     * Returns highest priority rule (lowest priority number) matching trigger
     *
     * @param string $trigger Trigger type
     * @return array|null Rule configuration or null if not found
     */
    private function getRuleForTrigger(string $trigger): ?array
    {
        if (!$this->forwarding_rules) {
            return null;
        }

        // Filter rules by trigger, sort by priority (ascending = higher priority)
        $matchingRules = collect($this->forwarding_rules)
            ->filter(fn($rule) => $rule['trigger'] === $trigger)
            ->sortBy('priority')
            ->values();

        return $matchingRules->first();
    }

    /**
     * Check if current time is within active hours
     *
     * @return bool
     */
    private function isWithinActiveHours(): bool
    {
        if (!$this->active_hours) {
            return true; // No restrictions = always active
        }

        $now = Carbon::now($this->timezone);
        $dayOfWeek = strtolower($now->format('l')); // 'monday', 'tuesday', etc.
        $currentTime = $now->format('H:i');

        // Check if today has active hours
        if (!isset($this->active_hours[$dayOfWeek])) {
            return false;
        }

        $todayHours = $this->active_hours[$dayOfWeek];

        // Check each time range for today
        foreach ($todayHours as $timeRange) {
            if (is_string($timeRange) && str_contains($timeRange, '-')) {
                [$start, $end] = explode('-', $timeRange);

                if ($currentTime >= $start && $currentTime <= $end) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Evaluate rule conditions
     *
     * @param array $conditions Rule conditions to evaluate
     * @param array $context Execution context
     * @return bool True if all conditions pass
     */
    private function evaluateConditions(array $conditions, array $context): bool
    {
        // time_window: all|business_hours|after_hours
        if (isset($conditions['time_window'])) {
            switch ($conditions['time_window']) {
                case 'all':
                    break; // Always pass

                case 'business_hours':
                    if (!$this->isWithinBusinessHours()) {
                        return false;
                    }
                    break;

                case 'after_hours':
                    if ($this->isWithinBusinessHours()) {
                        return false;
                    }
                    break;
            }
        }

        // outside_business_hours: boolean
        if (isset($conditions['outside_business_hours']) && $conditions['outside_business_hours']) {
            if ($this->isWithinBusinessHours()) {
                return false;
            }
        }

        // after_attempts: number (from context)
        if (isset($conditions['after_attempts'])) {
            $attempts = $context['attempts'] ?? 0;
            if ($attempts < $conditions['after_attempts']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current time is within branch business hours
     *
     * @return bool
     */
    private function isWithinBusinessHours(): bool
    {
        $branch = $this->branch;
        if (!$branch || !$branch->business_hours) {
            return true; // No hours defined = always open
        }

        $now = Carbon::now($this->timezone);
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        $businessHours = $branch->business_hours;

        if (!isset($businessHours[$dayOfWeek])) {
            return false; // Closed today
        }

        foreach ($businessHours[$dayOfWeek] as $timeRange) {
            if (is_string($timeRange) && str_contains($timeRange, '-')) {
                [$start, $end] = explode('-', $timeRange);

                if ($currentTime >= $start && $currentTime <= $end) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Validate forwarding rules and trigger types
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Validate forwarding rules structure
            if ($model->forwarding_rules) {
                foreach ($model->forwarding_rules as $rule) {
                    if (!isset($rule['trigger']) || !isset($rule['target_number'])) {
                        throw new \InvalidArgumentException(
                            'Each forwarding rule must have "trigger" and "target_number" fields'
                        );
                    }

                    if (!in_array($rule['trigger'], self::TRIGGERS)) {
                        throw new \InvalidArgumentException(
                            "Invalid trigger type: {$rule['trigger']}"
                        );
                    }
                }
            }
        });
    }
}
