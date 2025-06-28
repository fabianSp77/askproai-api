<div class="p-6">
    @php
        use App\Models\Appointment;
        use App\Models\Call;
        
        $events = collect();
        
        // Add customer creation
        $events->push([
            'date' => $customer->created_at,
            'type' => 'created',
            'title' => 'Kunde registriert',
            'description' => 'Kunde wurde im System angelegt',
            'icon' => 'heroicon-o-user-plus',
            'color' => 'primary',
        ]);
        
        // Add appointments
        foreach ($customer->appointments()->with(['service', 'staff', 'branch'])->get() as $appointment) {
            $events->push([
                'date' => $appointment->starts_at,
                'type' => 'appointment',
                'title' => $appointment->service?->name ?? 'Termin',
                'description' => sprintf(
                    '%s bei %s%s',
                    $appointment->starts_at->format('H:i') . ' Uhr',
                    $appointment->staff?->name ?? 'Mitarbeiter',
                    $appointment->branch ? ' - ' . $appointment->branch->name : ''
                ),
                'icon' => 'heroicon-o-calendar',
                'color' => match($appointment->status) {
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    'no_show' => 'warning',
                    default => 'info',
                },
                'status' => match($appointment->status) {
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgesagt',
                    'no_show' => 'Nicht erschienen',
                    'confirmed' => 'Bestätigt',
                    default => 'Geplant',
                },
                'link' => \App\Filament\Admin\Resources\AppointmentResource::getUrl('view', ['record' => $appointment]),
            ]);
        }
        
        // Add calls
        foreach ($customer->calls()->get() as $call) {
            $events->push([
                'date' => $call->created_at,
                'type' => 'call',
                'title' => 'Anruf',
                'description' => sprintf(
                    'Dauer: %s, Stimmung: %s',
                    gmdate('i:s', $call->duration_sec ?? 0),
                    match($call->sentiment ?? $call->analysis['sentiment'] ?? null) {
                        'positive' => '😊 Positiv',
                        'negative' => '😞 Negativ',
                        'neutral' => '😐 Neutral',
                        default => 'Unbekannt',
                    }
                ),
                'icon' => 'heroicon-o-phone',
                'color' => 'gray',
                'link' => \App\Filament\Admin\Resources\CallResource::getUrl('view', ['record' => $call]),
            ]);
        }
        
        // Add notes (if relation exists)
        if (method_exists($customer, 'notes')) {
            foreach ($customer->notes as $note) {
                $events->push([
                    'date' => $note->created_at,
                    'type' => 'note',
                    'title' => 'Notiz hinzugefügt',
                    'description' => \Str::limit($note->content, 100),
                    'icon' => 'heroicon-o-document-text',
                    'color' => 'gray',
                ]);
            }
        }
        
        // Sort events by date descending
        $events = $events->sortByDesc('date');
        
        // Group events by month
        $groupedEvents = $events->groupBy(function ($event) {
            return $event['date']->format('F Y');
        });
    @endphp
    
    <div class="space-y-8">
        @foreach($groupedEvents as $month => $monthEvents)
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">
                    {{ \Carbon\Carbon::parse($month)->locale('de')->isoFormat('MMMM YYYY') }}
                </h3>
                
                <div class="relative">
                    @foreach($monthEvents as $index => $event)
                        <div class="flex gap-x-3 {{ $index < count($monthEvents) - 1 ? 'pb-6' : '' }}">
                            <!-- Timeline line -->
                            @if($index < count($monthEvents) - 1)
                                <div class="absolute left-[1.125rem] top-10 h-full w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                            @endif
                            
                            <!-- Icon -->
                            <div class="relative flex h-9 w-9 items-center justify-center rounded-full bg-{{ $event['color'] }}-100 dark:bg-{{ $event['color'] }}-900/20">
                                <x-filament::icon
                                    :icon="$event['icon']"
                                    class="h-5 w-5 text-{{ $event['color'] }}-600 dark:text-{{ $event['color'] }}-400"
                                />
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 pt-1">
                                <div class="flex items-start justify-between gap-x-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-x-2">
                                            <h4 class="text-sm font-medium text-gray-950 dark:text-white">
                                                @if(isset($event['link']))
                                                    <a href="{{ $event['link'] }}" class="hover:underline">
                                                        {{ $event['title'] }}
                                                    </a>
                                                @else
                                                    {{ $event['title'] }}
                                                @endif
                                            </h4>
                                            @if(isset($event['status']))
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium 
                                                    {{ match($event['color']) {
                                                        'success' => 'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400',
                                                        'danger' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-400',
                                                        'warning' => 'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                                                    } }}">
                                                    {{ $event['status'] }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($event['description'])
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $event['description'] }}
                                            </p>
                                        @endif
                                    </div>
                                    <time class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $event['date']->format('d.m. H:i') }}
                                    </time>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        
        @if($events->isEmpty())
            <div class="text-center py-12">
                <x-filament::icon
                    icon="heroicon-o-clock"
                    class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600"
                />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    Keine Aktivitäten
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Für diesen Kunden wurden noch keine Aktivitäten aufgezeichnet.
                </p>
            </div>
        @endif
    </div>
</div>