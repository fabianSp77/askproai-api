@php
    $record = $getRecord();
    $relationships = [];

    // ════════════════════════════════════════════════════════════════════
    // CASE 1: This call BOOKED appointments that were LATER CANCELLED
    // ════════════════════════════════════════════════════════════════════
    $cancelledAppointments = $record->appointments()
        ->where('status', 'cancelled')
        ->with(['modifications' => function ($q) {
            $q->where('modification_type', 'cancel')
              ->latest('created_at');
        }])
        ->get();

    foreach ($cancelledAppointments as $appointment) {
        $cancellationSummary = $appointment->getCancellationSummary();
        $cancellationCallId = $cancellationSummary['cancellation_call_id'] ?? null;

        if ($cancellationCallId && $cancellationCallId !== $record->id) {
            $relationships[] = [
                'type' => 'cancelled_by',
                'call_id' => $cancellationCallId,
                'service' => $appointment->service?->name ?? 'Service',
                'date' => $appointment->starts_at ? \Carbon\Carbon::parse($appointment->starts_at)->format('d.m.Y H:i') : null,
            ];
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // CASE 2: This call PERFORMED CANCELLATIONS of previous bookings
    // ════════════════════════════════════════════════════════════════════
    if ($record->retell_call_id) {
        $performedMods = \App\Models\AppointmentModification::query()
            ->where('modification_type', 'cancel')
            ->whereJsonContains('metadata->call_id', $record->retell_call_id)
            ->with(['appointment.service', 'appointment.call'])
            ->get();

        foreach ($performedMods as $mod) {
            $appointment = $mod->appointment;
            if ($appointment && $appointment->call_id && $appointment->call_id !== $record->id) {
                $relationships[] = [
                    'type' => 'cancels',
                    'call_id' => $appointment->call_id,
                    'service' => $appointment->service?->name ?? 'Service',
                    'date' => $appointment->starts_at ? \Carbon\Carbon::parse($appointment->starts_at)->format('d.m.Y H:i') : null,
                ];
            }
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // NO RELATIONSHIPS FOUND
    // ════════════════════════════════════════════════════════════════════
    if (empty($relationships)) {
        echo '<span style="color: #9ca3af; font-size: 0.75rem;">-</span>';
        return;
    }

    // ════════════════════════════════════════════════════════════════════
    // GROUP BY TYPE AND RENDER
    // ════════════════════════════════════════════════════════════════════
    $cancelledBy = array_filter($relationships, fn($r) => $r['type'] === 'cancelled_by');
    $cancels = array_filter($relationships, fn($r) => $r['type'] === 'cancels');
@endphp

<div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.75rem;">
    @if (!empty($cancelledBy))
        {{-- This call's appointments were LATER CANCELLED --}}
        @foreach ($cancelledBy as $rel)
            <a href="{{ \App\Filament\Resources\CallResource::getUrl('view', ['record' => $rel['call_id']]) }}"
               style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; background-color: #fed7aa; color: #c2410c; text-decoration: none; font-weight: 500; transition: background-color 0.2s;"
               onmouseover="this.style.backgroundColor='#fdba74'"
               onmouseout="this.style.backgroundColor='#fed7aa'"
               title="Termin wurde storniert: {{ $rel['service'] }} am {{ $rel['date'] }}">
                <svg style="width: 0.875rem; height: 0.875rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
                <span>Storniert in #{{ $rel['call_id'] }}</span>
            </a>
        @endforeach
    @endif

    @if (!empty($cancels))
        {{-- This call CANCELLED previous bookings --}}
        @foreach ($cancels as $rel)
            <a href="{{ \App\Filament\Resources\CallResource::getUrl('view', ['record' => $rel['call_id']]) }}"
               style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; background-color: #dbeafe; color: #1e40af; text-decoration: none; font-weight: 500; transition: background-color 0.2s;"
               onmouseover="this.style.backgroundColor='#bfdbfe'"
               onmouseout="this.style.backgroundColor='#dbeafe'"
               title="Stornierung von Termin: {{ $rel['service'] }} (gebucht in Call #{{ $rel['call_id'] }})">
                <svg style="width: 0.875rem; height: 0.875rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                </svg>
                <span>Buchung aus #{{ $rel['call_id'] }}</span>
            </a>
        @endforeach
    @endif
</div>
