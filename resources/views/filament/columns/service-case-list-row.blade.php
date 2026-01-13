@php
    /**
     * ServiceNow-Style 3-Zeilen Summary Row für Service Cases Liste
     *
     * Zeile 1: Ticket-ID + Priorität-Badge + Status-Badge
     * Zeile 2: Betreff (gekürzt, Tooltip für vollständig)
     * Zeile 3: Kategorie | Anrufer | SLA-Status
     *
     * @since 2025-12-31 Phase 2 UI/UX Optimierung
     */
    $record = $getRecord();

    // Ticket ID
    $ticketId = $record->formatted_id ?? 'TKT-' . str_pad($record->id, 5, '0', STR_PAD_LEFT);

    // Status Badge
    $status = $record->status ?? 'new';
    $statusColor = match($status) {
        'new' => 'gray',
        'open' => 'info',
        'pending' => 'warning',
        'resolved' => 'success',
        'closed' => 'gray',
        default => 'gray',
    };
    $statusLabel = match($status) {
        'new' => 'Neu',
        'open' => 'Offen',
        'pending' => 'Wartend',
        'resolved' => 'Gelöst',
        'closed' => 'Geschlossen',
        default => ucfirst($status),
    };

    // Priority Badge mit Icon
    $priority = $record->priority ?? 'normal';
    $priorityColor = match($priority) {
        'low' => 'gray',
        'normal' => 'info',
        'high' => 'warning',
        'critical' => 'danger',
        default => 'gray',
    };
    $priorityLabel = match($priority) {
        'low' => 'Niedrig',
        'normal' => 'Normal',
        'high' => 'Hoch',
        'critical' => 'Kritisch',
        default => ucfirst($priority),
    };
    $priorityIcon = match($priority) {
        'low' => '↓',
        'normal' => '–',
        'high' => '↑',
        'critical' => '🔥',
        default => '',
    };

    // Betreff
    $subject = $record->subject ?? '';
    $subjectShort = \Illuminate\Support\Str::limit($subject, 50);

    // Kategorie
    $categoryName = $record->category?->name ?? 'Ohne Kategorie';

    // Anrufer
    $callerName = $record->ai_metadata['customer_name'] ?? $record->customer?->name ?? 'Unbekannt';
    $callerPhone = $record->ai_metadata['customer_phone'] ?? null;

    // SLA Status
    $slaStatus = 'ok';
    $slaLabel = '';
    $slaColor = 'success';

    if ($record->isResolutionOverdue()) {
        $slaStatus = 'resolution_overdue';
        $slaLabel = '⏰ Lösung überfällig';
        $slaColor = 'danger';
    } elseif ($record->isResponseOverdue()) {
        $slaStatus = 'response_overdue';
        $slaLabel = '⏰ Antwort überfällig';
        $slaColor = 'danger';
    } elseif ($record->sla_resolution_due_at && now()->diffInHours($record->sla_resolution_due_at, false) < 4 && now()->diffInHours($record->sla_resolution_due_at, false) > 0) {
        $slaStatus = 'warning';
        $hours = now()->diffInHours($record->sla_resolution_due_at, false);
        $slaLabel = "⚠️ {$hours}h verbleibend";
        $slaColor = 'warning';
    } elseif ($record->sla_resolution_due_at) {
        $slaStatus = 'ok';
        $slaLabel = '✓ SLA OK';
        $slaColor = 'success';
    }

    // Output Status (für Troubleshooting)
    $outputStatus = $record->output_status ?? 'pending';
    $hasOutputIssue = in_array($outputStatus, ['failed', 'partial']);

    // Tooltip
    $tooltipLines = [
        "🎫 {$ticketId}",
        "Status: {$statusLabel}",
        "Priorität: {$priorityLabel}",
        "",
        "Betreff: {$subject}",
        "Kategorie: {$categoryName}",
        "",
        "Anrufer: {$callerName}",
    ];
    if ($callerPhone) {
        $tooltipLines[] = "Telefon: {$callerPhone}";
    }
    if ($record->sla_resolution_due_at) {
        $tooltipLines[] = "";
        $tooltipLines[] = "SLA Lösung: " . \Carbon\Carbon::parse($record->sla_resolution_due_at)->format('d.m.Y H:i');
    }
    if ($hasOutputIssue) {
        $tooltipLines[] = "";
        $tooltipLines[] = "⚠️ Output: " . ucfirst($outputStatus);
    }
    $tooltip = implode("\n", $tooltipLines);

    // View URL
    $viewUrl = \App\Filament\Resources\ServiceCaseResource::getUrl('view', ['record' => $record->id]);
@endphp

{{-- ServiceNow-Style 3-Zeilen Summary --}}
<div class="flex flex-col gap-1 py-1" title="{{ $tooltip }}">
    {{-- Zeile 1: Ticket-ID + Badges --}}
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ $viewUrl }}"
           class="text-sm font-bold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 focus-visible:outline-offset-1 focus-visible:rounded"
           title="Ticket {{ $ticketId }} anzeigen">
            {{ $ticketId }}
        </a>

        {{-- Priority Badge --}}
        <x-filament::badge :color="$priorityColor" size="sm" class="cursor-help">
            {{ $priorityIcon }} {{ $priorityLabel }}
        </x-filament::badge>

        {{-- Status Badge --}}
        <x-filament::badge :color="$statusColor" size="sm" class="cursor-help">
            {{ $statusLabel }}
        </x-filament::badge>

        {{-- Output Issue Indicator --}}
        @if($hasOutputIssue)
            <x-filament::badge color="danger" size="sm" class="cursor-help" title="Output {{ ucfirst($outputStatus) }}">
                ⚡ Output
            </x-filament::badge>
        @endif
    </div>

    {{-- Zeile 2: Betreff --}}
    @if($subject)
        <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-1" title="{{ $subject }}">
            {{ $subjectShort }}
        </div>
    @else
        <div class="text-sm text-gray-400 dark:text-gray-500 italic">
            Kein Betreff
        </div>
    @endif

    {{-- Zeile 3: Meta-Informationen --}}
    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
        {{-- Kategorie --}}
        <span class="flex items-center gap-1">
            <span class="text-gray-400">📁</span>
            <span>{{ \Illuminate\Support\Str::limit($categoryName, 20) }}</span>
        </span>

        <span class="text-gray-300 dark:text-gray-600">|</span>

        {{-- Anrufer --}}
        <span class="flex items-center gap-1">
            <span class="text-gray-400">👤</span>
            <span>{{ \Illuminate\Support\Str::limit($callerName, 20) }}</span>
        </span>

        {{-- SLA Status (nur wenn relevant) --}}
        @if($slaLabel)
            <span class="text-gray-300 dark:text-gray-600">|</span>
            @if($slaColor === 'danger')
                <span class="text-red-600 dark:text-red-400 font-medium">{{ $slaLabel }}</span>
            @elseif($slaColor === 'warning')
                <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $slaLabel }}</span>
            @else
                <span class="text-green-600 dark:text-green-500">{{ $slaLabel }}</span>
            @endif
        @endif

        {{-- Assigned To (wenn zugewiesen) --}}
        @if($record->assignedTo)
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span class="flex items-center gap-1">
                <span class="text-gray-400">🎯</span>
                <span class="text-primary-600 dark:text-primary-400">{{ \Illuminate\Support\Str::limit($record->assignedTo->name, 15) }}</span>
            </span>
        @elseif($record->assignedGroup)
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span class="flex items-center gap-1">
                <span class="text-gray-400">👥</span>
                <span>{{ \Illuminate\Support\Str::limit($record->assignedGroup->name, 15) }}</span>
            </span>
        @endif
    </div>
</div>
