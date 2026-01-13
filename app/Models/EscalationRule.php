<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * EscalationRule Model
 *
 * ServiceNow-style automated escalation rules.
 * Triggers on SLA breaches, idle time, or priority changes.
 * Opt-in per company.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $trigger_type
 * @property int|null $trigger_minutes
 * @property array|null $conditions
 * @property string $action_type
 * @property array $action_config
 * @property bool $is_active
 * @property int $execution_order
 * @property \Illuminate\Support\Carbon|null $last_executed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection|EscalationRuleExecution[] $executions
 */
class EscalationRule extends Model
{
    use HasFactory, BelongsToCompany;

    // Trigger types
    public const TRIGGER_SLA_RESPONSE_BREACH = 'sla_response_breach';
    public const TRIGGER_SLA_RESOLUTION_BREACH = 'sla_resolution_breach';
    public const TRIGGER_SLA_RESPONSE_WARNING = 'sla_response_warning';
    public const TRIGGER_SLA_RESOLUTION_WARNING = 'sla_resolution_warning';
    public const TRIGGER_IDLE_TIME = 'idle_time';
    public const TRIGGER_PRIORITY_CHANGE = 'priority_change';

    public const TRIGGER_TYPES = [
        self::TRIGGER_SLA_RESPONSE_BREACH => 'SLA Response überschritten',
        self::TRIGGER_SLA_RESOLUTION_BREACH => 'SLA Resolution überschritten',
        self::TRIGGER_SLA_RESPONSE_WARNING => 'SLA Response Warnung',
        self::TRIGGER_SLA_RESOLUTION_WARNING => 'SLA Resolution Warnung',
        self::TRIGGER_IDLE_TIME => 'Keine Aktivität',
        self::TRIGGER_PRIORITY_CHANGE => 'Priorität erhöht',
    ];

    // Action types
    public const ACTION_NOTIFY_EMAIL = 'notify_email';
    public const ACTION_REASSIGN_GROUP = 'reassign_group';
    public const ACTION_ESCALATE_PRIORITY = 'escalate_priority';
    public const ACTION_NOTIFY_WEBHOOK = 'notify_webhook';

    public const ACTION_TYPES = [
        self::ACTION_NOTIFY_EMAIL => 'E-Mail Benachrichtigung',
        self::ACTION_REASSIGN_GROUP => 'Gruppe neu zuweisen',
        self::ACTION_ESCALATE_PRIORITY => 'Priorität erhöhen',
        self::ACTION_NOTIFY_WEBHOOK => 'Webhook aufrufen',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'trigger_type',
        'trigger_minutes',
        'conditions',
        'action_type',
        'action_config',
        'is_active',
        'execution_order',
        'last_executed_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'action_config' => 'array',
        'is_active' => 'boolean',
        'execution_order' => 'integer',
        'trigger_minutes' => 'integer',
        'last_executed_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(EscalationRuleExecution::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('execution_order', 'asc');
    }

    public function scopeByTrigger(Builder $query, string $triggerType): Builder
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Only rules for companies with escalation enabled.
     */
    public function scopeEnabledCompanies(Builder $query): Builder
    {
        return $query->whereHas('company', function ($q) {
            $q->where('escalation_rules_enabled', true);
        });
    }

    // ========================================
    // CONDITION MATCHING
    // ========================================

    /**
     * Check if a service case matches this rule's conditions.
     */
    public function matchesCase(ServiceCase $case): bool
    {
        $conditions = $this->conditions ?? [];

        // Check priority filter
        if (!empty($conditions['priorities'])) {
            if (!in_array($case->priority, $conditions['priorities'])) {
                return false;
            }
        }

        // Check case type filter
        if (!empty($conditions['case_types'])) {
            if (!in_array($case->case_type, $conditions['case_types'])) {
                return false;
            }
        }

        // Check category filter
        if (!empty($conditions['category_ids'])) {
            if (!in_array($case->category_id, $conditions['category_ids'])) {
                return false;
            }
        }

        // Check assigned group filter
        if (!empty($conditions['assigned_group_ids'])) {
            if (!in_array($case->assigned_group_id, $conditions['assigned_group_ids'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the trigger condition is met for a case.
     */
    public function shouldTrigger(ServiceCase $case): bool
    {
        // Don't trigger for closed/resolved cases
        if (in_array($case->status, [ServiceCase::STATUS_RESOLVED, ServiceCase::STATUS_CLOSED])) {
            return false;
        }

        $now = now();

        switch ($this->trigger_type) {
            case self::TRIGGER_SLA_RESPONSE_BREACH:
                return $case->sla_response_due_at
                    && $case->sla_response_due_at < $now
                    && !$case->sla_response_met_at;

            case self::TRIGGER_SLA_RESOLUTION_BREACH:
                return $case->sla_resolution_due_at
                    && $case->sla_resolution_due_at < $now;

            case self::TRIGGER_SLA_RESPONSE_WARNING:
                if (!$case->sla_response_due_at || $case->sla_response_met_at) {
                    return false;
                }
                $warningTime = $case->sla_response_due_at->subMinutes($this->trigger_minutes ?? 30);
                return $now >= $warningTime && $now < $case->sla_response_due_at;

            case self::TRIGGER_SLA_RESOLUTION_WARNING:
                if (!$case->sla_resolution_due_at) {
                    return false;
                }
                $warningTime = $case->sla_resolution_due_at->subMinutes($this->trigger_minutes ?? 60);
                return $now >= $warningTime && $now < $case->sla_resolution_due_at;

            case self::TRIGGER_IDLE_TIME:
                $lastActivity = $case->updated_at;
                $idleMinutes = $now->diffInMinutes($lastActivity);
                return $idleMinutes >= ($this->trigger_minutes ?? 120);

            case self::TRIGGER_PRIORITY_CHANGE:
                // This is event-driven, not time-based
                return false;

            default:
                return false;
        }
    }

    // ========================================
    // ACCESSORS
    // ========================================

    public function getTriggerLabelAttribute(): string
    {
        return self::TRIGGER_TYPES[$this->trigger_type] ?? $this->trigger_type;
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? $this->action_type;
    }

    /**
     * Get human-readable trigger description.
     */
    public function getTriggerDescriptionAttribute(): string
    {
        $label = $this->trigger_label;

        if ($this->trigger_minutes) {
            if (in_array($this->trigger_type, [self::TRIGGER_SLA_RESPONSE_WARNING, self::TRIGGER_SLA_RESOLUTION_WARNING])) {
                return "{$label} ({$this->trigger_minutes} Min vorher)";
            }
            if ($this->trigger_type === self::TRIGGER_IDLE_TIME) {
                return "{$label} (nach {$this->trigger_minutes} Min)";
            }
        }

        return $label;
    }
}
