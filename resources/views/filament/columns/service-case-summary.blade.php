@php
    $record = $getRecord();
    $serviceCases = $record->serviceCases ?? collect();
    $primaryCase = $serviceCases->first();

    if (!$primaryCase) {
        $showColumn = false;
    } else {
        $showColumn = true;
        $ticketId = $primaryCase->formatted_id ?? 'TKT-' . str_pad($primaryCase->id, 5, '0', STR_PAD_LEFT);
        $status = $primaryCase->status ?? 'new';
        $subject = \Illuminate\Support\Str::limit($primaryCase->subject ?? '', 30);
        $fullSubject = $primaryCase->subject ?? '';
        $totalCases = $serviceCases->count();
        $moreCases = $totalCases - 1;

        // 🎫 2025-12-28: Filament Color System für Dark Mode Support
        $badgeColor = match($status) {
            'new' => 'gray',
            'open' => 'info',       // Filament: blau
            'pending' => 'warning', // Filament: amber
            'resolved' => 'success',// Filament: grün
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

        // 🎫 2025-12-28: Ausführlicher Tooltip für bessere UX
        $tooltipLines = [];
        $tooltipLines[] = "🎫 {$ticketId}";
        $tooltipLines[] = "Status: {$statusLabel}";
        if ($fullSubject) {
            $tooltipLines[] = "Betreff: " . \Illuminate\Support\Str::limit($fullSubject, 50);
        }
        if ($primaryCase->category?->name) {
            $tooltipLines[] = "Kategorie: {$primaryCase->category->name}";
        }
        if ($moreCases > 0) {
            $tooltipLines[] = "";
            $tooltipLines[] = "+{$moreCases} weitere " . ($moreCases === 1 ? 'Ticket' : 'Tickets');
        }
        $tooltip = implode("\n", $tooltipLines);
    }
@endphp

@if($showColumn)
{{-- 🎫 2025-12-28: Ticket-Spalte (Hover entfernt für subtileres Design) --}}
<div class="flex flex-col gap-1" title="{{ $tooltip }}">
    {{-- Row 1: Ticket ID + Status Badge --}}
    <div class="flex items-center gap-1.5">
        <a href="{{ \App\Filament\Resources\ServiceCaseResource::getUrl('view', ['record' => $primaryCase->id]) }}"
           class="text-xs font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 focus-visible:outline-offset-1 focus-visible:rounded"
           title="Ticket {{ $ticketId }} anzeigen">
            {{ $ticketId }}
        </a>
        {{-- 🎫 2025-12-28: Filament Badge mit cursor-help für Tooltip-Hinweis --}}
        <x-filament::badge :color="$badgeColor" size="sm" class="cursor-help">
            {{ $statusLabel }}
        </x-filament::badge>
    </div>

    {{-- Row 2: Subject (kontrollierter Umbruch mit line-clamp) --}}
    @if($subject)
    <div class="text-xs text-gray-600 dark:text-gray-400 line-clamp-1" title="{{ $fullSubject }}">
        {{ $subject }}
    </div>
    @endif

    {{-- Row 3: Additional tickets indicator (if > 1) --}}
    @if($moreCases > 0)
        <div class="text-xs text-gray-400 dark:text-gray-500">
            +{{ $moreCases }} weitere {{ $moreCases === 1 ? 'Ticket' : 'Tickets' }}
        </div>
    @endif
</div>
@else
<div class="text-xs text-gray-400 dark:text-gray-500">
    —
</div>
@endif
