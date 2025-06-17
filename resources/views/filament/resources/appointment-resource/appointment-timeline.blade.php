@php
    $appointment = $getRecord();
    $events = collect();
    
    // Erstellung
    $events->push([
        'date' => $appointment->created_at,
        'type' => 'created',
        'title' => 'Termin erstellt',
        'description' => 'Der Termin wurde im System angelegt',
        'icon' => 'heroicon-o-plus-circle',
        'color' => 'primary',
    ]);
    
    // Cal.com Sync
    if ($appointment->calcom_booking_id || $appointment->calcom_v2_booking_id) {
        $events->push([
            'date' => $appointment->created_at->addSeconds(5),
            'type' => 'synced',
            'title' => 'Mit Cal.com synchronisiert',
            'description' => 'Booking ID: ' . ($appointment->calcom_v2_booking_id ?? $appointment->calcom_booking_id),
            'icon' => 'heroicon-o-cloud-arrow-down',
            'color' => 'info',
        ]);
    }
    
    // Anruf-Verknüpfung
    if ($appointment->call) {
        $events->push([
            'date' => $appointment->call->created_at,
            'type' => 'call',
            'title' => 'Aus Anruf erstellt',
            'description' => 'Termin wurde während eines Anrufs gebucht (' . gmdate('i:s', $appointment->call->duration_sec) . ')',
            'icon' => 'heroicon-o-phone',
            'color' => 'success',
        ]);
    }
    
    // Status-Änderungen (simuliert basierend auf Status)
    if ($appointment->status === 'confirmed') {
        $events->push([
            'date' => $appointment->created_at->addMinutes(30),
            'type' => 'confirmed',
            'title' => 'Termin bestätigt',
            'description' => 'Der Kunde hat den Termin bestätigt',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'info',
        ]);
    }
    
    // Erinnerungen
    if ($appointment->reminder_24h_sent_at) {
        $events->push([
            'date' => $appointment->reminder_24h_sent_at,
            'type' => 'reminder',
            'title' => '24h Erinnerung versendet',
            'description' => 'E-Mail/SMS Erinnerung wurde versendet',
            'icon' => 'heroicon-o-bell',
            'color' => 'warning',
        ]);
    }
    
    if ($appointment->reminder_2h_sent_at) {
        $events->push([
            'date' => $appointment->reminder_2h_sent_at,
            'type' => 'reminder',
            'title' => '2h Erinnerung versendet',
            'description' => 'E-Mail/SMS Erinnerung wurde versendet',
            'icon' => 'heroicon-o-bell-alert',
            'color' => 'warning',
        ]);
    }
    
    if ($appointment->reminder_30m_sent_at) {
        $events->push([
            'date' => $appointment->reminder_30m_sent_at,
            'type' => 'reminder',
            'title' => '30min Erinnerung versendet',
            'description' => 'Letzte Erinnerung wurde versendet',
            'icon' => 'heroicon-o-bell-snooze',
            'color' => 'danger',
        ]);
    }
    
    // Status: Abgeschlossen
    if ($appointment->status === 'completed') {
        $events->push([
            'date' => $appointment->updated_at,
            'type' => 'completed',
            'title' => 'Termin abgeschlossen',
            'description' => 'Der Termin wurde erfolgreich durchgeführt',
            'icon' => 'heroicon-o-check-badge',
            'color' => 'success',
        ]);
    }
    
    // Status: Abgesagt
    if ($appointment->status === 'cancelled') {
        $events->push([
            'date' => $appointment->updated_at,
            'type' => 'cancelled',
            'title' => 'Termin abgesagt',
            'description' => 'Der Termin wurde storniert',
            'icon' => 'heroicon-o-x-circle',
            'color' => 'danger',
        ]);
    }
    
    // Status: No-Show
    if ($appointment->status === 'no_show') {
        $events->push([
            'date' => $appointment->updated_at,
            'type' => 'no_show',
            'title' => 'Kunde nicht erschienen',
            'description' => 'Der Kunde ist nicht zum Termin erschienen',
            'icon' => 'heroicon-o-user-minus',
            'color' => 'danger',
        ]);
    }
    
    // Letzte Aktualisierung
    if ($appointment->updated_at->diffInMinutes($appointment->created_at) > 5) {
        $events->push([
            'date' => $appointment->updated_at,
            'type' => 'updated',
            'title' => 'Termin aktualisiert',
            'description' => 'Termindaten wurden geändert',
            'icon' => 'heroicon-o-pencil',
            'color' => 'gray',
        ]);
    }
    
    // Nach Datum sortieren
    $events = $events->sortBy('date');
@endphp

<div class="space-y-4">
    @if($events->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <x-heroicon-o-clock class="w-12 h-12 mx-auto mb-2 text-gray-400" />
            <p>Keine Timeline-Ereignisse vorhanden</p>
        </div>
    @else
        <div class="relative">
            <!-- Vertikale Linie -->
            <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
            
            @foreach($events as $event)
                <div class="relative flex items-start mb-6">
                    <!-- Icon -->
                    <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-gray-800 ring-4 ring-white dark:ring-gray-800">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-{{ $event['color'] }}-100 dark:bg-{{ $event['color'] }}-900/20">
                            <x-dynamic-component 
                                :component="$event['icon']" 
                                class="w-4 h-4 text-{{ $event['color'] }}-600 dark:text-{{ $event['color'] }}-400"
                            />
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="ml-4 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $event['title'] }}
                            </h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $event['date']->format('d.m.Y H:i') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $event['description'] }}
                        </p>
                        @if($event['date']->diffInHours(now()) < 24)
                            <span class="inline-flex items-center gap-1 mt-1 text-xs text-gray-500">
                                <x-heroicon-o-clock class="w-3 h-3" />
                                {{ $event['date']->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
            
            <!-- Termin-Zeitpunkt markieren -->
            @if($appointment->starts_at)
                <div class="relative flex items-start mb-6">
                    <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-gray-800 ring-4 ring-white dark:ring-gray-800">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-primary-100 dark:bg-primary-900/20">
                            <x-heroicon-o-calendar-days class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                Geplanter Termin
                            </h4>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $appointment->starts_at->format('d.m.Y H:i') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $appointment->service?->name ?? 'Keine Leistung angegeben' }}
                            @if($appointment->staff)
                                mit {{ $appointment->staff->name }}
                            @endif
                        </p>
                        @if($appointment->starts_at->isFuture())
                            <span class="inline-flex items-center gap-1 mt-1 text-xs text-primary-600">
                                <x-heroicon-o-clock class="w-3 h-3" />
                                In {{ $appointment->starts_at->diffForHumans() }}
                            </span>
                        @elseif($appointment->starts_at->isPast() && $appointment->status !== 'completed' && $appointment->status !== 'cancelled')
                            <span class="inline-flex items-center gap-1 mt-1 text-xs text-warning-600">
                                <x-heroicon-o-exclamation-triangle class="w-3 h-3" />
                                Überfällig seit {{ $appointment->starts_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>