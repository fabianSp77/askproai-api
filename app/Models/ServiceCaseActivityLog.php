<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceCaseActivityLog Model
 *
 * PURPOSE: Immutable audit trail for ServiceCase changes
 *
 * TRACKED ACTIONS:
 * - created: Initial case creation
 * - status_changed: Status transitions (new → open → resolved → closed)
 * - priority_changed: Priority escalation/de-escalation
 * - urgency_changed: Urgency level changes
 * - assigned: Case assigned to staff member
 * - group_assigned: Case assigned to group
 * - category_changed: Category reassignment
 * - customer_linked: Customer matched to case
 * - output_status_changed: Delivery status changes
 * - enrichment_completed: Transcript/audio enrichment finished
 * - note_added: Comment/note added
 * - escalated: Case escalated (SLA breach or manual)
 *
 * COMPLIANCE:
 * - GDPR: Tracks data modifications with user attribution
 * - SOC2: Audit trail for access control
 * - Multi-Tenancy: Scoped by company_id
 */
class ServiceCaseActivityLog extends Model
{
    /**
     * Disable updated_at - records are immutable
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'service_case_id',
        'company_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'reason',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // ==========================================
    // ACTION CONSTANTS
    // ==========================================

    public const ACTION_CREATED = 'created';
    public const ACTION_STATUS_CHANGED = 'status_changed';
    public const ACTION_PRIORITY_CHANGED = 'priority_changed';
    public const ACTION_URGENCY_CHANGED = 'urgency_changed';
    public const ACTION_ASSIGNED = 'assigned';
    public const ACTION_GROUP_ASSIGNED = 'group_assigned';
    public const ACTION_CATEGORY_CHANGED = 'category_changed';
    public const ACTION_CUSTOMER_LINKED = 'customer_linked';
    public const ACTION_OUTPUT_STATUS_CHANGED = 'output_status_changed';
    public const ACTION_ENRICHMENT_COMPLETED = 'enrichment_completed';
    public const ACTION_NOTE_ADDED = 'note_added';
    public const ACTION_ESCALATED = 'escalated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';

    /**
     * German labels for actions (for UI display)
     */
    public const ACTION_LABELS = [
        self::ACTION_CREATED => 'Erstellt',
        self::ACTION_STATUS_CHANGED => 'Status geändert',
        self::ACTION_PRIORITY_CHANGED => 'Priorität geändert',
        self::ACTION_URGENCY_CHANGED => 'Dringlichkeit geändert',
        self::ACTION_ASSIGNED => 'Zugewiesen',
        self::ACTION_GROUP_ASSIGNED => 'Gruppe zugewiesen',
        self::ACTION_CATEGORY_CHANGED => 'Kategorie geändert',
        self::ACTION_CUSTOMER_LINKED => 'Kunde verknüpft',
        self::ACTION_OUTPUT_STATUS_CHANGED => 'Ausgabestatus geändert',
        self::ACTION_ENRICHMENT_COMPLETED => 'Anreicherung abgeschlossen',
        self::ACTION_NOTE_ADDED => 'Notiz hinzugefügt',
        self::ACTION_ESCALATED => 'Eskaliert',
        self::ACTION_DELETED => 'Gelöscht',
        self::ACTION_RESTORED => 'Wiederhergestellt',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to logs for a specific case
     */
    public function scopeForCase($query, int $serviceCaseId)
    {
        return $query->where('service_case_id', $serviceCaseId);
    }

    /**
     * Scope to logs by a specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to a specific action type
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to recent logs (last N days)
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to company (multi-tenancy)
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // ==========================================
    // BUSINESS LOGIC
    // ==========================================

    /**
     * Create an activity log entry
     *
     * @param ServiceCase $case The case being logged
     * @param string $action The action constant (ACTION_*)
     * @param User|null $user The user performing the action
     * @param array|null $oldValues Previous field values
     * @param array|null $newValues New field values
     * @param string|null $reason Optional reason for the change
     * @return self
     */
    public static function logAction(
        ServiceCase $case,
        string $action,
        ?User $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): self {
        return self::create([
            'service_case_id' => $case->id,
            'company_id' => $case->company_id,
            'user_id' => $user?->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => substr(request()?->userAgent() ?? '', 0, 255),
            'reason' => $reason,
        ]);
    }

    /**
     * Get human-readable action description (German)
     */
    public function getActionDescriptionAttribute(): string
    {
        $actor = $this->user ? $this->user->name : 'System';
        $label = self::ACTION_LABELS[$this->action] ?? $this->action;

        return match ($this->action) {
            self::ACTION_CREATED => "{$actor} hat den Fall erstellt",
            self::ACTION_STATUS_CHANGED => "{$actor} hat den Status geändert",
            self::ACTION_PRIORITY_CHANGED => "{$actor} hat die Priorität geändert",
            self::ACTION_URGENCY_CHANGED => "{$actor} hat die Dringlichkeit geändert",
            self::ACTION_ASSIGNED => "{$actor} hat den Fall zugewiesen",
            self::ACTION_GROUP_ASSIGNED => "{$actor} hat die Gruppe zugewiesen",
            self::ACTION_CATEGORY_CHANGED => "{$actor} hat die Kategorie geändert",
            self::ACTION_CUSTOMER_LINKED => "{$actor} hat einen Kunden verknüpft",
            self::ACTION_OUTPUT_STATUS_CHANGED => "{$actor} hat den Ausgabestatus geändert",
            self::ACTION_ENRICHMENT_COMPLETED => "Anreicherung wurde abgeschlossen",
            self::ACTION_NOTE_ADDED => "{$actor} hat eine Notiz hinzugefügt",
            self::ACTION_ESCALATED => "{$actor} hat den Fall eskaliert",
            self::ACTION_DELETED => "{$actor} hat den Fall gelöscht",
            self::ACTION_RESTORED => "{$actor} hat den Fall wiederhergestellt",
            default => "{$actor} hat {$label} durchgeführt",
        };
    }

    /**
     * Get the action label (German)
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /**
     * Get a summary of changes between old and new values
     */
    public function getChangesSummaryAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Get formatted change details for display
     */
    public function getFormattedChangeAttribute(): ?string
    {
        $changes = $this->changes_summary;

        if (empty($changes)) {
            return null;
        }

        // Map common field changes to readable strings
        $fieldLabels = [
            'status' => 'Status',
            'priority' => 'Priorität',
            'urgency' => 'Dringlichkeit',
            'assigned_to' => 'Zugewiesen an',
            'assigned_group_id' => 'Zugewiesene Gruppe',
            'category_id' => 'Kategorie',
            'customer_id' => 'Kunde',
            'output_status' => 'Ausgabestatus',
            'enrichment_status' => 'Anreicherungsstatus',
        ];

        $parts = [];
        foreach ($changes as $field => $change) {
            $label = $fieldLabels[$field] ?? $field;
            $from = $this->formatValue($field, $change['from']);
            $to = $this->formatValue($field, $change['to']);
            $parts[] = "{$label}: {$from} → {$to}";
        }

        return implode(', ', $parts);
    }

    /**
     * Format a value for display (translate status/priority labels)
     */
    protected function formatValue(string $field, mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        // Use ServiceCase labels for known fields
        return match ($field) {
            'status' => ServiceCase::STATUS_LABELS[$value] ?? $value,
            'priority' => ServiceCase::PRIORITY_LABELS[$value] ?? $value,
            'output_status' => ServiceCase::OUTPUT_STATUS_LABELS[$value] ?? $value,
            default => (string) $value,
        };
    }

    // ==========================================
    // REPORTING HELPERS
    // ==========================================

    /**
     * Get status change count for a case
     */
    public static function getStatusChangeCount(int $serviceCaseId): int
    {
        return self::forCase($serviceCaseId)
            ->action(self::ACTION_STATUS_CHANGED)
            ->count();
    }

    /**
     * Get assignment history for a case
     */
    public static function getAssignmentHistory(int $serviceCaseId): \Illuminate\Support\Collection
    {
        return self::forCase($serviceCaseId)
            ->whereIn('action', [self::ACTION_ASSIGNED, self::ACTION_GROUP_ASSIGNED])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user's recent activity across all cases
     */
    public static function getUserActivity(int $userId, int $days = 30): \Illuminate\Support\Collection
    {
        return self::byUser($userId)
            ->recent($days)
            ->with('serviceCase')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get timeline for a case (most recent first)
     */
    public static function getTimeline(int $serviceCaseId, int $limit = 50): \Illuminate\Support\Collection
    {
        return self::forCase($serviceCaseId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
