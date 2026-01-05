<?php

namespace App\Filament\Resources\ServiceCaseResource\Widgets;

use App\Models\ServiceCase;
use App\Models\ServiceCaseActivityLog;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

/**
 * ServiceNow-Style Activity Timeline Widget
 *
 * Displays chronological case events from the activity log database.
 *
 * Primary: Uses ServiceCaseActivityLog table (real audit trail)
 * Fallback: Derives events from model timestamps for legacy cases
 *
 * Events tracked:
 * - created → Case created
 * - status_changed → Status transitions
 * - assigned → Staff/group assignment
 * - customer_linked → Customer matched
 * - output_sent_at → Output delivered
 * - enriched_at → Transcript enrichment
 */
class ServiceCaseActivityTimelineWidget extends Widget
{
    protected static string $view = 'filament.resources.service-case-resource.widgets.activity-timeline';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * Build activity list from real logs OR model timestamps (fallback)
     */
    public function getActivities(): array
    {
        if (!$this->record || !$this->record instanceof ServiceCase) {
            return [];
        }

        // Try to load real activity logs from database
        $logs = ServiceCaseActivityLog::where('service_case_id', $this->record->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // If we have real logs, use them
        if ($logs->isNotEmpty()) {
            return $this->buildActivitiesFromLogs($logs);
        }

        // FALLBACK: Legacy timestamp-based activities for cases without logs
        return $this->buildActivitiesFromTimestamps();
    }

    /**
     * Build activities from real ServiceCaseActivityLog records
     */
    protected function buildActivitiesFromLogs($logs): array
    {
        $activities = [];

        foreach ($logs as $log) {
            $activities[] = [
                'type' => $log->action,
                'icon' => $this->getIconForAction($log->action),
                'color' => $this->getColorForAction($log->action),
                'title' => $log->action_label,
                'description' => $this->getDescriptionForLog($log),
                'timestamp' => $log->created_at,
                'actor' => $log->user?->name ?? 'System',
            ];
        }

        return $activities;
    }

    /**
     * FALLBACK: Build activities from model timestamps (for legacy cases)
     */
    protected function buildActivitiesFromTimestamps(): array
    {
        $activities = [];

        // 1. Case Created
        $activities[] = [
            'type' => 'created',
            'icon' => 'heroicon-o-plus-circle',
            'color' => 'success',
            'title' => 'Case erstellt',
            'description' => $this->getCreationSource(),
            'timestamp' => $this->record->created_at,
            'actor' => $this->getCreationActor(),
        ];

        // 2. Enrichment completed (if applicable)
        if ($this->record->enriched_at) {
            $activities[] = [
                'type' => 'enriched',
                'icon' => 'heroicon-o-sparkles',
                'color' => 'info',
                'title' => 'Anreicherung abgeschlossen',
                'description' => $this->getEnrichmentDescription(),
                'timestamp' => $this->record->enriched_at,
                'actor' => 'System (Enrichment Job)',
            ];
        }

        // 3. Output sent
        if ($this->record->output_sent_at) {
            $activities[] = [
                'type' => 'output_sent',
                'icon' => 'heroicon-o-paper-airplane',
                'color' => 'success',
                'title' => 'Output gesendet',
                'description' => 'Email/Webhook erfolgreich zugestellt',
                'timestamp' => $this->record->output_sent_at,
                'actor' => 'System (Delivery Job)',
            ];
        }

        // 4. Output failed (current state)
        if ($this->record->output_status === ServiceCase::OUTPUT_FAILED) {
            $activities[] = [
                'type' => 'output_failed',
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
                'title' => 'Output fehlgeschlagen',
                'description' => $this->record->output_error ?? 'Unbekannter Fehler bei der Zustellung',
                'timestamp' => $this->record->updated_at,
                'actor' => 'System',
            ];
        }

        // 5. Enrichment timeout
        if ($this->record->enrichment_status === ServiceCase::ENRICHMENT_TIMEOUT) {
            $activities[] = [
                'type' => 'enrichment_timeout',
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'title' => 'Anreicherung Timeout',
                'description' => 'Transcript-Daten konnten nicht rechtzeitig abgerufen werden',
                'timestamp' => $this->record->updated_at,
                'actor' => 'System',
            ];
        }

        // 6. Status resolved/closed
        if (in_array($this->record->status, [ServiceCase::STATUS_RESOLVED, ServiceCase::STATUS_CLOSED])) {
            $statusLabel = $this->record->status === ServiceCase::STATUS_RESOLVED ? 'Gelöst' : 'Geschlossen';
            $activities[] = [
                'type' => 'status_change',
                'icon' => $this->record->status === ServiceCase::STATUS_RESOLVED
                    ? 'heroicon-o-check-circle'
                    : 'heroicon-o-archive-box',
                'color' => 'success',
                'title' => "Case {$statusLabel}",
                'description' => "Status wurde auf '{$statusLabel}' gesetzt",
                'timestamp' => $this->record->updated_at,
                'actor' => 'Benutzer',
            ];
        }

        // Sort by timestamp descending (newest first)
        usort($activities, fn($a, $b) => $b['timestamp']->timestamp - $a['timestamp']->timestamp);

        return $activities;
    }

    /**
     * Get icon for an action type
     */
    protected function getIconForAction(string $action): string
    {
        return match ($action) {
            ServiceCaseActivityLog::ACTION_CREATED => 'heroicon-o-plus-circle',
            ServiceCaseActivityLog::ACTION_STATUS_CHANGED => 'heroicon-o-arrow-path',
            ServiceCaseActivityLog::ACTION_PRIORITY_CHANGED => 'heroicon-o-flag',
            ServiceCaseActivityLog::ACTION_URGENCY_CHANGED => 'heroicon-o-exclamation-triangle',
            ServiceCaseActivityLog::ACTION_ASSIGNED => 'heroicon-o-user',
            ServiceCaseActivityLog::ACTION_GROUP_ASSIGNED => 'heroicon-o-user-group',
            ServiceCaseActivityLog::ACTION_CATEGORY_CHANGED => 'heroicon-o-folder',
            ServiceCaseActivityLog::ACTION_CUSTOMER_LINKED => 'heroicon-o-link',
            ServiceCaseActivityLog::ACTION_OUTPUT_STATUS_CHANGED => 'heroicon-o-paper-airplane',
            ServiceCaseActivityLog::ACTION_ENRICHMENT_COMPLETED => 'heroicon-o-sparkles',
            ServiceCaseActivityLog::ACTION_NOTE_ADDED => 'heroicon-o-chat-bubble-left-right',
            ServiceCaseActivityLog::ACTION_ESCALATED => 'heroicon-o-arrow-trending-up',
            ServiceCaseActivityLog::ACTION_DELETED => 'heroicon-o-trash',
            ServiceCaseActivityLog::ACTION_RESTORED => 'heroicon-o-arrow-uturn-left',
            default => 'heroicon-o-information-circle',
        };
    }

    /**
     * Get color for an action type
     */
    protected function getColorForAction(string $action): string
    {
        return match ($action) {
            ServiceCaseActivityLog::ACTION_CREATED => 'success',
            ServiceCaseActivityLog::ACTION_STATUS_CHANGED => 'info',
            ServiceCaseActivityLog::ACTION_PRIORITY_CHANGED => 'warning',
            ServiceCaseActivityLog::ACTION_URGENCY_CHANGED => 'warning',
            ServiceCaseActivityLog::ACTION_ASSIGNED => 'primary',
            ServiceCaseActivityLog::ACTION_GROUP_ASSIGNED => 'primary',
            ServiceCaseActivityLog::ACTION_CATEGORY_CHANGED => 'gray',
            ServiceCaseActivityLog::ACTION_CUSTOMER_LINKED => 'success',
            ServiceCaseActivityLog::ACTION_OUTPUT_STATUS_CHANGED => 'info',
            ServiceCaseActivityLog::ACTION_ENRICHMENT_COMPLETED => 'info',
            ServiceCaseActivityLog::ACTION_NOTE_ADDED => 'gray',
            ServiceCaseActivityLog::ACTION_ESCALATED => 'danger',
            ServiceCaseActivityLog::ACTION_DELETED => 'danger',
            ServiceCaseActivityLog::ACTION_RESTORED => 'success',
            default => 'gray',
        };
    }

    /**
     * Get description for a log entry
     */
    protected function getDescriptionForLog(ServiceCaseActivityLog $log): string
    {
        // Use the formatted change if available
        if ($log->formatted_change) {
            return $log->formatted_change;
        }

        // Use reason if provided
        if ($log->reason) {
            return $log->reason;
        }

        return $log->action_description;
    }

    /**
     * Determine creation source based on available data
     */
    protected function getCreationSource(): string
    {
        if ($this->record->call_id) {
            return 'Service Case wurde aus einem Voice-AI Anruf erstellt';
        }

        if ($this->record->ai_metadata) {
            return 'Service Case wurde über die KI-Schnittstelle erstellt';
        }

        return 'Service Case wurde manuell erstellt';
    }

    /**
     * Determine creation actor
     */
    protected function getCreationActor(): string
    {
        if ($this->record->call_id || !empty($this->record->ai_metadata)) {
            return 'Voice AI (Retell)';
        }

        return 'System';
    }

    /**
     * Build enrichment description
     */
    protected function getEnrichmentDescription(): string
    {
        $parts = [];

        if ($this->record->transcript_segment_count) {
            $parts[] = "{$this->record->transcript_segment_count} Transcript-Segmente";
        }

        if ($this->record->retell_call_session_id) {
            $parts[] = "Call Session verknüpft";
        }

        return !empty($parts)
            ? 'Case mit Transcript-Daten angereichert: ' . implode(', ', $parts)
            : 'Case mit zusätzlichen Daten angereichert';
    }

    /**
     * Get total activity count
     */
    public function getActivityCount(): int
    {
        return count($this->getActivities());
    }
}
