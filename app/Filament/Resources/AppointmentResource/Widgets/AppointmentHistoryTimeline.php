<?php

namespace App\Filament\Resources\AppointmentResource\Widgets;

use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

/**
 * Appointment History Timeline Widget
 *
 * Displays chronological timeline of all appointment lifecycle events:
 * - Creation (booking)
 * - Reschedules (time changes)
 * - Cancellations
 * - Associated calls
 *
 * Implementation Date: 2025-10-11
 * Implemented for: Call 834 History Visualization
 */
class AppointmentHistoryTimeline extends Widget
{
    protected static string $view = 'filament.resources.appointment-resource.widgets.appointment-history-timeline';

    public ?Appointment $record = null;

    protected int | string | array $columnSpan = 'full';

    /**
     * PERFORMANCE OPTIMIZATION: Cache for modifications to prevent N+1 queries
     * Populated once in getTimelineData(), reused in helper methods
     */
    protected ?array $modificationsCache = null;

    /**
     * PERFORMANCE OPTIMIZATION: Cache for call lookups
     * Prevents duplicate Call::find() queries in timeline rendering
     */
    protected array $callCache = [];

    /**
     * Get timeline data for rendering
     *
     * Combines data from:
     * - appointments table (rescheduled_at, cancelled_at, etc.)
     * - appointment_modifications table
     * - calls table (via call_id)
     *
     * @return array Timeline events sorted chronologically
     */
    public function getTimelineData(): array
    {
        if (!$this->record) {
            return [];
        }

        $timeline = [];

        // 1. CREATION EVENT
        // NULL SAFETY FIX 2025-10-11: Handle NULL starts_at gracefully
        // TYPE SAFETY FIX 2025-10-11: Validate call_id is integer (could be STRING from legacy data)
        $creationCallId = null;
        if ($this->record->call_id) {
            if (is_numeric($this->record->call_id) && (int) $this->record->call_id > 0) {
                $creationCallId = (int) $this->record->call_id;
            } else {
                \Log::warning('Timeline: Creation event has non-integer call_id', [
                    'appointment_id' => $this->record->id,
                    'call_id' => $this->record->call_id,
                    'type' => gettype($this->record->call_id)
                ]);
            }
        }

        $timeline[] = [
            'timestamp' => $this->record->created_at,
            'type' => 'created',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'title' => 'Termin erstellt',
            'description' => $this->getCreationDescription(),
            'actor' => $this->formatActor($this->record->created_by ?? $this->record->booking_source),
            'call_id' => $creationCallId,
            'metadata' => [
                'original_time' => $this->record->previous_starts_at
                    ? $this->record->previous_starts_at->format('d.m.Y H:i')
                    : ($this->record->starts_at ? $this->record->starts_at->format('d.m.Y H:i') : 'Unbekannt'),
                'booking_source' => $this->record->booking_source ?? $this->record->source,
            ],
        ];

        // 2. RESCHEDULE & CANCELLATION EVENTS
        // DEDUPLICATION FIX 2025-10-11: Removed duplicate event creation
        // These events are now ONLY sourced from appointment_modifications table (below)
        // to avoid showing the same action twice in the timeline.
        //
        // The appointments.rescheduled_at and appointments.cancelled_at fields are
        // denormalized cache fields for quick filtering/queries, not the authoritative
        // timeline source. The modifications table contains complete metadata and is
        // the single source of truth for the timeline display.

        // 4. ADD APPOINTMENT MODIFICATIONS FROM DATABASE
        // PERFORMANCE FIX (PERF-001): Load modifications once and cache
        $modifications = $this->record->modifications()
            ->orderBy('created_at', 'asc')
            ->get();

        // Cache for reuse in helper methods
        $this->modificationsCache = $modifications->groupBy('modification_type')->toArray();

        foreach ($modifications as $mod) {
            // FIX 2025-10-11: Validate metadata is array (could be NULL from legacy data)
            $metadata = $mod->metadata;
            if (!is_array($metadata)) {
                \Log::warning('Timeline: Invalid metadata type', [
                    'modification_id' => $mod->id,
                    'type' => gettype($metadata)
                ]);
                $metadata = [];
            }

            // FIX 2025-10-11: Extract call_id with proper type validation
            // Bug: Modification metadata can contain STRING Retell IDs (e.g., "call_abc123")
            // but getCallLink() expects integer database IDs
            $callId = null;
            if (isset($metadata['call_id'])) {
                $rawCallId = $metadata['call_id'];
                // Only use if it's a valid positive integer (database ID)
                // Ignore string Retell API IDs - they can't be linked to Call records
                if (is_numeric($rawCallId) && (int) $rawCallId > 0) {
                    $callId = (int) $rawCallId;
                }
            }

            $timeline[] = [
                'timestamp' => $mod->created_at,
                'type' => $mod->modification_type,
                'icon' => $this->getModificationIcon($mod->modification_type),
                'color' => $mod->within_policy ? 'success' : 'warning',
                'title' => $this->getModificationTitle($mod->modification_type),
                'description' => $this->getModificationDescription($mod),
                'actor' => $this->formatActor($mod->modified_by_type),
                'call_id' => $callId,  // ✅ Now guaranteed to be ?int (or null for Retell string IDs)
                'metadata' => [
                    'within_policy' => $mod->within_policy,
                    'fee_charged' => $mod->fee_charged,
                    'reason' => $mod->reason,
                    'details' => $mod->metadata,
                ],
            ];
        }

        // 5. SORT REVERSE CHRONOLOGICALLY (newest first, oldest last)
        // USER REQUEST 2025-10-11: Neueste Aktion oben, älteste unten
        usort($timeline, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];  // Reversed for DESC order
        });

        return $timeline;
    }

    /**
     * Get creation description with booking details
     *
     * SECURITY: All user-generated content is properly escaped to prevent XSS
     */
    protected function getCreationDescription(): string
    {
        $time = $this->record->previous_starts_at
            ? $this->record->previous_starts_at
            : $this->record->starts_at;

        $parts = [];

        // FIX 2025-10-11: NULL safety - time could be NULL
        if ($time) {
            $parts[] = "Gebucht für <strong>" . e($time->format('d.m.Y H:i')) . " Uhr</strong>";
        } else {
            $parts[] = "Gebucht für <strong>Unbekannte Zeit</strong>";
        }

        // FIX 2025-10-11: Use optional chaining for service relation
        if ($this->record->service?->name) {
            // SECURITY FIX (VULN-001): Escape service name to prevent XSS
            $parts[] = "Dienstleistung: " . e($this->record->service->name);
        }

        if ($this->record->booking_source) {
            $source = match($this->record->booking_source) {
                'retell_phone', 'retell_api', 'retell_webhook' => 'KI-Telefonsystem',
                'cal.com_direct', 'cal.com_webhook' => 'Online-Buchung',
                'manual_admin' => 'Admin Portal',
                default => e($this->record->booking_source), // SECURITY: Escape unknown sources
            };
            $parts[] = "Quelle: " . $source;
        }

        return implode('<br>', $parts);
    }

    /**
     * Get reschedule description with old/new times
     *
     * SECURITY: All output is escaped, NULL safety added
     */
    protected function getRescheduleDescription(): string
    {
        // NULL SAFETY: Handle missing timestamps gracefully
        $oldTime = $this->record->previous_starts_at?->format('H:i') ?? 'N/A';
        $newTime = $this->record->starts_at?->format('H:i') ?? 'N/A';
        $date = $this->record->starts_at?->format('d.m.Y') ?? 'N/A';

        return sprintf(
            'Von <strong>%s Uhr</strong> verschoben auf <strong>%s Uhr</strong><br>Datum: %s',
            e($oldTime),
            e($newTime),
            e($date)
        );
    }

    /**
     * Get cancellation description with reason
     *
     * SECURITY: Escape cancellation_reason to prevent XSS
     * PERFORMANCE: Use cached modifications to prevent N+1
     */
    protected function getCancellationDescription(): string
    {
        $parts = [];

        if ($this->record->cancellation_reason) {
            // SECURITY FIX (VULN-001): Escape user-provided cancellation reason
            $parts[] = "Grund: " . e($this->record->cancellation_reason);
        }

        $hoursNotice = $this->calculateHoursNotice($this->record->cancelled_at, $this->record->starts_at);
        if ($hoursNotice) {
            $parts[] = "Vorwarnung: " . e(round($hoursNotice, 1)) . " Stunden";
        }

        // PERFORMANCE FIX (PERF-001): Get from cached modifications instead of new query
        $cancelMod = $this->getLatestModificationByType('cancel');

        if ($cancelMod && $cancelMod->fee_charged > 0) {
            $parts[] = "<strong>Gebühr: " . e(number_format($cancelMod->fee_charged, 2)) . " €</strong>";
        } else {
            $parts[] = "Gebühr: 0,00 €";
        }

        return implode('<br>', $parts);
    }

    /**
     * Get modification title based on type
     *
     * DEDUPLICATION FIX 2025-10-11: Simplified titles (removed "erfasst")
     * to match the style of the creation event
     */
    protected function getModificationTitle(string $type): string
    {
        return match($type) {
            'reschedule' => 'Termin verschoben',
            'cancel' => 'Termin storniert',
            'create' => 'Termin erstellt',
            default => ucfirst($type),
        };
    }

    /**
     * Get modification description from metadata
     *
     * SECURITY: All user-provided data is escaped to prevent XSS
     */
    protected function getModificationDescription($modification): string
    {
        $parts = [];

        $metadata = $modification->metadata ?? [];

        if ($modification->modification_type === 'reschedule') {
            if (isset($metadata['original_time']) && isset($metadata['new_time'])) {
                try {
                    $oldTime = \Carbon\Carbon::parse($metadata['original_time'])->format('H:i');
                    $newTime = \Carbon\Carbon::parse($metadata['new_time'])->format('H:i');
                    // SECURITY: Escape times (though they're system-generated, defense in depth)
                    $parts[] = "Von " . e($oldTime) . " → " . e($newTime) . " Uhr";
                } catch (\Exception $e) {
                    $parts[] = "Zeitänderung: Format-Fehler";
                }
            }

            if (isset($metadata['calcom_synced'])) {
                $syncStatus = $metadata['calcom_synced'] ? '✅ Synchronisiert' : '⚠️ Nicht synchronisiert';
                $parts[] = "Kalendersystem: {$syncStatus}";
            }
        }

        if ($modification->modification_type === 'cancel') {
            if (isset($metadata['hours_notice']) && is_numeric($metadata['hours_notice'])) {
                // SECURITY: Validate numeric value before display
                $parts[] = "Vorwarnung: " . e(round($metadata['hours_notice'], 1)) . " Stunden";
            }
        }

        if ($modification->within_policy) {
            $parts[] = '✅ Innerhalb Richtlinien';
        } else {
            $parts[] = '⚠️ Außerhalb Richtlinien';
        }

        if ($modification->reason) {
            // SECURITY FIX (VULN-001): Escape user-provided reason
            $parts[] = "Grund: " . e($modification->reason);
        }

        return implode('<br>', $parts);
    }

    /**
     * Get icon for modification type
     */
    protected function getModificationIcon(string $type): string
    {
        return match($type) {
            'reschedule' => 'heroicon-o-arrow-path',
            'cancel' => 'heroicon-o-x-circle',
            'create' => 'heroicon-o-plus-circle',
            default => 'heroicon-o-document-text',
        };
    }

    /**
     * Format actor name for display
     */
    protected function formatActor(?string $actor): string
    {
        if (!$actor) {
            return 'System';
        }

        return match($actor) {
            'retell_ai', 'retell_api', 'retell_phone' => 'Kunde (Telefon)',
            'customer', 'customer_phone', 'customer_web' => 'Kunde',
            'admin_user' => 'Administrator',
            'staff_user' => 'Mitarbeiter',
            'cal.com_webhook', 'cal.com' => 'Online-Buchung',
            'system_cron', 'system' => 'System',
            default => ucfirst($actor),
        };
    }

    /**
     * Calculate hours notice between two timestamps
     */
    protected function calculateHoursNotice($fromTime, $toTime): ?float
    {
        if (!$fromTime || !$toTime) {
            return null;
        }

        return \Carbon\Carbon::parse($fromTime)->diffInHours(\Carbon\Carbon::parse($toTime), true);
    }

    /**
     * Get policy tooltip text for timeline event
     *
     * Shows which rules were checked and their results in a tooltip
     * Example: "3 von 3 Regeln erfüllt\n✅ Vorwarnung: 80h (min. 24h)\n✅ Monatslimit: 2/10"
     *
     * User Request: 2025-10-11 - Show policy details on hover/click
     *
     * @param array $event Timeline event data
     * @return string|null Formatted tooltip text or null if no policy data
     */
    public function getPolicyTooltip(array $event): ?string
    {
        if (!isset($event['metadata']['within_policy'])) {
            return null;
        }

        $details = $event['metadata']['details'] ?? [];

        // FIX 2025-10-11: Validate details is array (could be NULL)
        if (!is_array($details)) {
            $details = [];
        }

        $withinPolicy = $event['metadata']['within_policy'];

        $rules = [];
        $passedCount = 0;
        $totalCount = 0;

        // RULE 1: Hours Notice (always checked for cancellations/reschedules)
        if (isset($details['hours_notice']) && isset($details['policy_required'])) {
            $totalCount++;
            $hours = round($details['hours_notice'], 1);
            $required = $details['policy_required'];

            if ($hours >= $required) {
                $passedCount++;
                $buffer = round($hours - $required, 1);
                $rules[] = "✅ Vorwarnzeit: {$hours}h (min. {$required}h) +{$buffer}h Puffer";
            } else {
                $shortage = round($required - $hours, 1);
                $rules[] = "❌ Vorwarnzeit: {$hours}h (min. {$required}h erforderlich) -{$shortage}h zu kurz";
            }
        }

        // RULE 2: Monthly Quota (if checked)
        if (isset($details['quota_used']) && isset($details['quota_max'])) {
            $totalCount++;
            $used = $details['quota_used'];
            $max = $details['quota_max'];
            $remaining = $max - $used;

            if ($used <= $max) {
                $passedCount++;
                $rules[] = "✅ Monatslimit: {$used}/{$max} verwendet ({$remaining} verbleibend)";
            } else {
                $exceeded = $used - $max;
                $rules[] = "❌ Monatslimit: {$used}/{$max} ({$exceeded} überschritten)";
            }
        }

        // RULE 3: Per-Appointment Reschedule Limit (if checked)
        if (isset($details['reschedule_count']) && isset($details['max_allowed'])) {
            $totalCount++;
            $count = $details['reschedule_count'];
            $max = $details['max_allowed'];
            $remaining = $max - $count;

            if ($count <= $max) {
                $passedCount++;
                $rules[] = "✅ Termin-Limit: {$count}/{$max} Umbuchungen ({$remaining} verbleibend)";
            } else {
                $exceeded = $count - $max;
                $rules[] = "❌ Termin-Limit: {$count}/{$max} ({$exceeded} zu viel)";
            }
        }

        // RULE 4: Fee (always relevant)
        $totalCount++;
        $fee = $event['metadata']['fee_charged'] ?? 0;

        if ($fee == 0) {
            $passedCount++;
            $rules[] = "✅ Gebühr: Keine (0,00 €)";
        } else {
            // Fee charged doesn't necessarily mean failed, but it's notable
            $passedCount++; // Still counts as passed if within_policy is true
            $rules[] = "⚠️ Gebühr: " . number_format($fee, 2) . " €";
        }

        // Build summary header
        if ($withinPolicy) {
            $summary = "✅ {$passedCount} von {$totalCount} Regeln erfüllt";
        } else {
            $failedCount = $totalCount - $passedCount;
            $summary = "⚠️ {$failedCount} von {$totalCount} Regeln verletzt";
        }

        // Combine summary + rules
        return $summary . "\n\n" . implode("\n", $rules);
    }

    /**
     * Get latest modification by type (CACHED)
     *
     * PERFORMANCE FIX (PERF-001): Use cache instead of separate DB queries
     * SECURITY FIX (VULN-002): Validate metadata structure
     *
     * @param string $type Modification type (reschedule, cancel, create)
     * @return \App\Models\AppointmentModification|null
     */
    protected function getLatestModificationByType(string $type): ?\App\Models\AppointmentModification
    {
        // Use cache if available (populated in getTimelineData)
        if ($this->modificationsCache !== null && isset($this->modificationsCache[$type])) {
            $mods = $this->modificationsCache[$type];
            return is_array($mods) ? end($mods) : null;
        }

        // Fallback: Direct query (should rarely happen)
        return $this->record->modifications()
            ->where('modification_type', $type)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get call_id for reschedule event from modifications
     *
     * SECURITY FIX (VULN-002): Validate and cast call_id from metadata
     * BUG FIX (2025-10-11): Only return integer database IDs, not Retell API strings
     */
    protected function getCallIdForReschedule(): ?int
    {
        $rescheduleMod = $this->getLatestModificationByType('reschedule');

        if (!$rescheduleMod || !isset($rescheduleMod->metadata['call_id'])) {
            return $this->record->call_id;
        }

        $callId = $rescheduleMod->metadata['call_id'];

        // FIX: Only use if numeric AND positive (database ID)
        // Retell API IDs like "call_abc123" are strings - can't be used as database IDs
        if (is_numeric($callId) && (int) $callId > 0) {
            return (int) $callId;
        }

        // Fallback to appointment's call_id (or null if also invalid)
        return $this->record->call_id;
    }

    /**
     * Get call_id for cancellation event from modifications
     *
     * SECURITY FIX (VULN-002): Validate and cast call_id from metadata
     * BUG FIX (2025-10-11): Only return integer database IDs, not Retell API strings
     */
    protected function getCallIdForCancellation(): ?int
    {
        $cancelMod = $this->getLatestModificationByType('cancel');

        if (!$cancelMod || !isset($cancelMod->metadata['call_id'])) {
            return $this->record->call_id;
        }

        $callId = $cancelMod->metadata['call_id'];

        // FIX: Only use if numeric AND positive (database ID)
        // Retell API IDs like "call_abc123" are strings - can't be used as database IDs
        if (is_numeric($callId) && (int) $callId > 0) {
            return (int) $callId;
        }

        // Fallback to appointment's call_id (or null if also invalid)
        return $this->record->call_id;
    }

    /**
     * Get call link HTML if call exists
     *
     * SECURITY FIX (VULN-003): Add multi-tenant isolation check
     * PERFORMANCE FIX (PERF-002): Cache call lookups
     */
    public function getCallLink(?int $callId): ?HtmlString
    {
        if (!$callId) {
            return null;
        }

        // PERFORMANCE: Check cache first
        if (!isset($this->callCache[$callId])) {
            // SECURITY FIX (VULN-003): Add tenant isolation - only show calls from same company
            $this->callCache[$callId] = \App\Models\Call::where('id', $callId)
                ->where('company_id', $this->record->company_id)
                ->first();
        }

        $call = $this->callCache[$callId];

        if (!$call) {
            // Call not found OR belongs to different company (tenant isolation)
            return new HtmlString("<span class='text-gray-500'>Call #" . e($callId) . "</span>");
        }

        $url = route('filament.admin.resources.calls.view', ['record' => $call->id]);

        // SECURITY: Escape phone number to prevent XSS
        $phoneDisplay = $call->from_number ? " (" . e($call->from_number) . ")" : "";

        return new HtmlString(
            "<a href='" . e($url) . "' class='text-primary-600 hover:underline' target='_blank'>" .
            "📞 Call #" . e($callId) . $phoneDisplay .
            "</a>"
        );
    }
}
