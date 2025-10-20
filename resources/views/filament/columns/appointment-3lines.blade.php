@php
    $record = $getRecord();
    $appointment = $record->appointment;

    // Check for unfulfilled wishes
    $unresolvedWishes = $record->appointmentWishes()
        ->where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->first();

    // Tooltip content
    $tooltipLines = [];

    if (!$appointment) {
        if ($unresolvedWishes && $unresolvedWishes->desired_date) {
            $tooltipLines[] = "⏰ Terminwunsch:";
            $tooltipLines[] = "Datum: " . \Carbon\Carbon::parse($unresolvedWishes->desired_date)->format('d.m.Y');
            if ($unresolvedWishes->desired_time) {
                $tooltipLines[] = "Zeit: " . $unresolvedWishes->desired_time;
            }
            $tooltipLines[] = "Dauer: " . $unresolvedWishes->desired_duration . " Min";
            if ($unresolvedWishes->desired_service) {
                $tooltipLines[] = "Service: " . $unresolvedWishes->desired_service;
            }
        }
    } else {
        if ($appointment->starts_at) {
            $startDate = \Carbon\Carbon::parse($appointment->starts_at);
            $endDate = $appointment->ends_at ? \Carbon\Carbon::parse($appointment->ends_at) : null;

            $tooltipLines[] = "📅 Termin vereinbart:";
            $tooltipLines[] = "Datum: " . $startDate->format('d.m.Y');
            $tooltipLines[] = "Zeit: " . $startDate->format('H:i') . " - " . ($endDate ? $endDate->format('H:i') : '?');

            $duration = $endDate ? $startDate->diffInMinutes($endDate) : ($appointment->duration ?? '?');
            $tooltipLines[] = "Dauer: " . $duration . " Min";

            if ($appointment->service) {
                $tooltipLines[] = "Service: " . $appointment->service->name;
            }

            if ($appointment->staff) {
                $tooltipLines[] = "Mitarbeiter:in: " . $appointment->staff->name;
            } else {
                $tooltipLines[] = "⚠️ Mitarbeiter:in: Nicht zugewiesen";
            }
        }
    }

    $tooltipText = implode("\n", $tooltipLines);
@endphp

<div class="space-y-1" title="{{ $tooltipText }}">
    @if(!$appointment)
        @if($unresolvedWishes && $unresolvedWishes->desired_date)
            <!-- Unerfüllter Wunsch -->
            <div class="text-xs text-orange-600 font-medium">
                ⏰ Wunsch:
            </div>
            <div class="text-xs text-orange-600">
                {{ \Carbon\Carbon::parse($unresolvedWishes->desired_date)->format('d.m. H:i') }}
            </div>
        @else
            <!-- Kein Termin -->
            <div class="text-xs text-gray-400">
                Kein Termin
            </div>
        @endif
    @elseif(!$appointment->starts_at)
        <!-- Termin ohne Zeit -->
        <div class="text-xs text-green-600 font-medium">
            ✅ Vereinbart
        </div>
        <div class="text-xs text-gray-500">
            Zeit folgt
        </div>
    @else
        <!-- Vollständiger Termin mit 3 Zeilen -->
        @php
            $startDate = \Carbon\Carbon::parse($appointment->starts_at);
            $endDate = $appointment->ends_at ? \Carbon\Carbon::parse($appointment->ends_at) : null;
            $duration = $endDate ? $startDate->diffInMinutes($endDate) : ($appointment->duration ?? 30);
        @endphp

        <!-- Zeile 1: Datum -->
        <div class="text-xs font-medium text-gray-800">
            {{ $startDate->format('d.m.Y') }}
        </div>

        <!-- Zeile 2: Uhrzeit von-bis -->
        <div class="text-xs text-gray-600">
            {{ $startDate->format('H:i') }} - {{ $endDate ? $endDate->format('H:i') : '?' }}
        </div>

        <!-- Zeile 3: Dauer + Mitarbeiter Status -->
        <div class="text-xs text-gray-600">
            {{ $duration }} Min
            @if($appointment->staff)
                <span class="ml-1 text-xs text-green-700 font-medium">Zugewiesen</span>
            @else
                <span class="ml-1 text-xs text-orange-600 font-medium">Unzugewiesen</span>
            @endif
        </div>
    @endif
</div>
