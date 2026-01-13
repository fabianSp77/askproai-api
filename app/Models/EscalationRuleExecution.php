<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EscalationRuleExecution Model
 *
 * Audit log for escalation rule executions.
 *
 * @property int $id
 * @property int $escalation_rule_id
 * @property int $service_case_id
 * @property string $action_type
 * @property array|null $action_result
 * @property bool $success
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon $executed_at
 *
 * @property-read EscalationRule $rule
 * @property-read ServiceCase $serviceCase
 */
class EscalationRuleExecution extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'escalation_rule_id',
        'service_case_id',
        'action_type',
        'action_result',
        'success',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'action_result' => 'array',
        'success' => 'boolean',
        'executed_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function rule(): BelongsTo
    {
        return $this->belongsTo(EscalationRule::class, 'escalation_rule_id');
    }

    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class);
    }
}
