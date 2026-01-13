@php
    use App\Models\ServiceCase;

    // Status configuration
    $statusConfig = match($record->status) {
        ServiceCase::STATUS_NEW => [
            'color' => 'gray',
            'bgColor' => 'bg-gray-100 dark:bg-gray-800',
            'textColor' => 'text-gray-800 dark:text-gray-200',
            'icon' => 'heroicon-o-sparkles',
            'label' => 'Neu'
        ],
        ServiceCase::STATUS_OPEN => [
            'color' => 'info',
            'bgColor' => 'bg-blue-100 dark:bg-blue-900',
            'textColor' => 'text-blue-800 dark:text-blue-200',
            'icon' => 'heroicon-o-folder-open',
            'label' => 'Offen'
        ],
        ServiceCase::STATUS_PENDING => [
            'color' => 'warning',
            'bgColor' => 'bg-amber-100 dark:bg-amber-900',
            'textColor' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'heroicon-o-clock',
            'label' => 'Wartend'
        ],
        ServiceCase::STATUS_RESOLVED => [
            'color' => 'success',
            'bgColor' => 'bg-green-100 dark:bg-green-900',
            'textColor' => 'text-green-800 dark:text-green-200',
            'icon' => 'heroicon-o-check-circle',
            'label' => 'Gelöst'
        ],
        ServiceCase::STATUS_CLOSED => [
            'color' => 'primary',
            'bgColor' => 'bg-indigo-100 dark:bg-indigo-900',
            'textColor' => 'text-indigo-800 dark:text-indigo-200',
            'icon' => 'heroicon-o-archive-box',
            'label' => 'Geschlossen'
        ],
        default => [
            'color' => 'gray',
            'bgColor' => 'bg-gray-100 dark:bg-gray-800',
            'textColor' => 'text-gray-800 dark:text-gray-200',
            'icon' => 'heroicon-o-question-mark-circle',
            'label' => $record->status
        ],
    };

    // Priority configuration
    $priorityConfig = match($record->priority) {
        ServiceCase::PRIORITY_CRITICAL => [
            'bgColor' => 'bg-red-600',
            'textColor' => 'text-white',
            'label' => 'Kritisch',
            'pulse' => true
        ],
        ServiceCase::PRIORITY_HIGH => [
            'bgColor' => 'bg-orange-500',
            'textColor' => 'text-white',
            'label' => 'Hoch',
            'pulse' => false
        ],
        ServiceCase::PRIORITY_NORMAL => [
            'bgColor' => 'bg-blue-500',
            'textColor' => 'text-white',
            'label' => 'Normal',
            'pulse' => false
        ],
        ServiceCase::PRIORITY_LOW => [
            'bgColor' => 'bg-gray-400',
            'textColor' => 'text-white',
            'label' => 'Niedrig',
            'pulse' => false
        ],
        default => [
            'bgColor' => 'bg-gray-400',
            'textColor' => 'text-white',
            'label' => $record->priority ?? 'Normal',
            'pulse' => false
        ],
    };

    // Case type configuration
    $typeConfig = match($record->case_type) {
        ServiceCase::TYPE_INCIDENT => [
            'icon' => 'heroicon-o-exclamation-triangle',
            'label' => 'Störung',
            'color' => 'text-red-600 dark:text-red-400'
        ],
        ServiceCase::TYPE_REQUEST => [
            'icon' => 'heroicon-o-clipboard-document-list',
            'label' => 'Anfrage',
            'color' => 'text-amber-600 dark:text-amber-400'
        ],
        ServiceCase::TYPE_INQUIRY => [
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'label' => 'Anliegen',
            'color' => 'text-blue-600 dark:text-blue-400'
        ],
        default => [
            'icon' => 'heroicon-o-ticket',
            'label' => $record->case_type ?? 'Case',
            'color' => 'text-gray-600 dark:text-gray-400'
        ],
    };

    // Calculate age (cast to int to avoid float deprecation warnings)
    $ageHours = (int) $record->created_at->diffInHours(now());
    $ageDays = (int) floor($ageHours / 24);
    $remainingHours = $ageHours % 24;

    // SLA Status
    $slaOverdue = $record->isResponseOverdue() || $record->isResolutionOverdue();
@endphp

<header class="service-case-header p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition-all hover:shadow-md" role="region" aria-label="Service Case {{ $record->formatted_id }} - {{ $statusConfig['label'] }}">
    {{-- Top Row: Case ID + Status + Priority --}}
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
        {{-- Left: Case Number & Type --}}
        <div class="flex items-start gap-4">
            {{-- Large Case Number --}}
            <div class="flex-shrink-0">
                <div class="text-3xl md:text-4xl font-bold text-primary-600 dark:text-primary-400 font-mono tracking-tight" aria-label="Fall-Nummer {{ $record->formatted_id }}">
                    {{ $record->formatted_id }}
                </div>
                <div class="flex items-center gap-2 mt-1 {{ $typeConfig['color'] }}">
                    <x-dynamic-component :component="$typeConfig['icon']" class="w-4 h-4" aria-hidden="true" />
                    <span class="text-sm font-medium">{{ $typeConfig['label'] }}</span>
                </div>
            </div>

            {{-- Status & Priority Badges --}}
            <div class="flex flex-col gap-2">
                {{-- Status Badge (Large) --}}
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold {{ $statusConfig['bgColor'] }} {{ $statusConfig['textColor'] }}" role="status" aria-label="Status: {{ $statusConfig['label'] }}">
                    <x-dynamic-component :component="$statusConfig['icon']" class="w-5 h-5" aria-hidden="true" />
                    {{ $statusConfig['label'] }}
                </span>

                {{-- Priority Badge --}}
                <span @class([
                    'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold shadow-sm',
                    $priorityConfig['bgColor'],
                    $priorityConfig['textColor'],
                    'animate-pulse' => $priorityConfig['pulse'],
                ]) aria-label="Priorität: {{ $priorityConfig['label'] }}">
                    <span class="mr-1.5" aria-hidden="true">
                        @if($record->priority === ServiceCase::PRIORITY_CRITICAL)
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </span>
                    {{ $priorityConfig['label'] }}
                </span>
            </div>
        </div>

        {{-- Right: Quick Stats Grid --}}
        <div class="flex flex-wrap items-center gap-4 lg:gap-6 text-sm">
            {{-- Age --}}
            <div class="text-center px-3" aria-label="Alter: {{ $ageDays > 0 ? $ageDays . ' Tage ' . $remainingHours . ' Stunden' : $ageHours . ' Stunden' }}">
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide mb-1">Alter</div>
                <div class="font-bold text-gray-900 dark:text-white">
                    @if($ageDays > 0)
                        {{ $ageDays }}d {{ $remainingHours }}h
                    @else
                        {{ $ageHours }}h
                    @endif
                </div>
            </div>

            {{-- Created --}}
            <div class="text-center px-3 border-l border-gray-200 dark:border-gray-700">
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide mb-1">Erstellt</div>
                <time class="font-semibold text-gray-900 dark:text-white" datetime="{{ $record->created_at->toIso8601String() }}">
                    {{ $record->created_at->format('d.m.Y') }}
                </time>
                <div class="text-xs text-gray-400">{{ $record->created_at->format('H:i') }} Uhr</div>
            </div>

            {{-- Assigned --}}
            <div class="text-center px-3 border-l border-gray-200 dark:border-gray-700">
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide mb-1">Zugewiesen</div>
                <div class="font-semibold text-gray-900 dark:text-white">
                    {{ $record->assignedTo?->name ?? '—' }}
                </div>
            </div>

            {{-- Output Status --}}
            <div class="text-center px-3 border-l border-gray-200 dark:border-gray-700">
                <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide mb-1">Output</div>
                @php
                    $outputConfig = match($record->output_status) {
                        ServiceCase::OUTPUT_SENT => ['color' => 'success', 'label' => 'Gesendet', 'icon' => 'heroicon-o-check-circle'],
                        ServiceCase::OUTPUT_FAILED => ['color' => 'danger', 'label' => 'Fehler', 'icon' => 'heroicon-o-x-circle'],
                        default => ['color' => 'warning', 'label' => 'Ausstehend', 'icon' => 'heroicon-o-clock'],
                    };
                @endphp
                <x-filament::badge :color="$outputConfig['color']" size="sm">
                    {{ $outputConfig['label'] }}
                </x-filament::badge>
            </div>

            {{-- SLA Indicator --}}
            @if($record->sla_response_due_at || $record->sla_resolution_due_at)
                <div class="text-center px-3 border-l border-gray-200 dark:border-gray-700" role="status" aria-label="SLA Status: {{ $slaOverdue ? 'Überfällig' : 'Im Zeitplan' }}">
                    <div class="text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide mb-1">SLA</div>
                    @if($slaOverdue)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 animate-pulse" aria-live="polite">
                            <x-heroicon-o-exclamation-triangle class="w-3 h-3" aria-hidden="true" />
                            Überfällig
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
                            <x-heroicon-o-check-circle class="w-3 h-3" aria-hidden="true" />
                            OK
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Bottom Row: Subject & Category --}}
    <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white leading-tight">
            {{ $record->subject }}
        </h1>
        @if($record->category)
            <div class="mt-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <x-heroicon-o-tag class="w-4 h-4" aria-hidden="true" />
                <span>{{ $record->category->name }}</span>
                @if($record->category->description)
                    <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">|</span>
                    <span class="text-gray-400 dark:text-gray-500 text-xs">{{ Str::limit($record->category->description, 50) }}</span>
                @endif
            </div>
        @endif
    </div>
</header>
