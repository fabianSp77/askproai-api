<div class="space-y-4">
    @php
        $events = [];
        
        // Add appointment creation
        $events[] = [
            'date' => $getRecord()->created_at,
            'type' => 'created',
            'title' => 'Termin erstellt',
            'description' => 'Termin wurde im System angelegt',
            'icon' => 'heroicon-o-plus-circle',
            'color' => 'primary',
        ];
        
        // Add communication logs
        if (method_exists($getRecord(), 'communicationLogs')) {
            foreach ($getRecord()->communicationLogs as $log) {
                $events[] = [
                    'date' => $log->created_at,
                    'type' => 'communication',
                    'title' => match($log->type) {
                        'email' => 'E-Mail gesendet',
                        'sms' => 'SMS gesendet',
                        'phone' => 'Anruf getätigt',
                        'whatsapp' => 'WhatsApp gesendet',
                        default => 'Kommunikation',
                    },
                    'description' => $log->purpose ?? 'Nachricht an ' . $log->recipient,
                    'icon' => match($log->type) {
                        'email' => 'heroicon-o-envelope',
                        'sms' => 'heroicon-o-device-phone-mobile',
                        'phone' => 'heroicon-o-phone',
                        'whatsapp' => 'heroicon-o-chat-bubble-left-right',
                        default => 'heroicon-o-megaphone',
                    },
                    'color' => 'info',
                ];
            }
        }
        
        // Add reminder sent times
        if ($getRecord()->reminder_24h_sent_at) {
            $events[] = [
                'date' => $getRecord()->reminder_24h_sent_at,
                'type' => 'reminder',
                'title' => '24h Erinnerung gesendet',
                'description' => 'Automatische Erinnerung 24 Stunden vor dem Termin',
                'icon' => 'heroicon-o-bell',
                'color' => 'warning',
            ];
        }
        
        if ($getRecord()->reminder_2h_sent_at) {
            $events[] = [
                'date' => $getRecord()->reminder_2h_sent_at,
                'type' => 'reminder',
                'title' => '2h Erinnerung gesendet',
                'description' => 'Automatische Erinnerung 2 Stunden vor dem Termin',
                'icon' => 'heroicon-o-bell-alert',
                'color' => 'warning',
            ];
        }
        
        if ($getRecord()->reminder_30m_sent_at) {
            $events[] = [
                'date' => $getRecord()->reminder_30m_sent_at,
                'type' => 'reminder',
                'title' => '30min Erinnerung gesendet',
                'description' => 'Letzte Erinnerung 30 Minuten vor dem Termin',
                'icon' => 'heroicon-o-bell-snooze',
                'color' => 'danger',
            ];
        }
        
        // Add status changes (would need to be tracked in activity log)
        // For now, just add current status
        if ($getRecord()->status === 'completed' && $getRecord()->updated_at->ne($getRecord()->created_at)) {
            $events[] = [
                'date' => $getRecord()->updated_at,
                'type' => 'status',
                'title' => 'Termin abgeschlossen',
                'description' => 'Termin wurde als abgeschlossen markiert',
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ];
        } elseif ($getRecord()->status === 'cancelled') {
            $events[] = [
                'date' => $getRecord()->updated_at,
                'type' => 'status',
                'title' => 'Termin abgesagt',
                'description' => 'Termin wurde storniert',
                'icon' => 'heroicon-o-x-circle',
                'color' => 'danger',
            ];
        } elseif ($getRecord()->status === 'no_show') {
            $events[] = [
                'date' => $getRecord()->updated_at,
                'type' => 'status',
                'title' => 'Kunde nicht erschienen',
                'description' => 'Kunde ist nicht zum Termin erschienen',
                'icon' => 'heroicon-o-user-minus',
                'color' => 'warning',
            ];
        }
        
        // Add appointment date as future event if not yet happened
        if ($getRecord()->starts_at->isFuture()) {
            $events[] = [
                'date' => $getRecord()->starts_at,
                'type' => 'appointment',
                'title' => 'Terminzeitpunkt',
                'description' => 'Geplanter Termin',
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'primary',
                'future' => true,
            ];
        }
        
        // Sort events by date
        usort($events, fn($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);
    @endphp
    
    <div class="relative">
        @foreach($events as $index => $event)
            <div class="flex gap-x-3 {{ $index < count($events) - 1 ? 'pb-8' : '' }}">
                <!-- Timeline line -->
                @if($index < count($events) - 1)
                    <div class="absolute left-[1.125rem] top-10 h-full w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                @endif
                
                <!-- Icon -->
                <div class="relative flex h-9 w-9 items-center justify-center rounded-full 
                    {{ isset($event['future']) && $event['future'] 
                        ? 'bg-gray-100 dark:bg-gray-800 ring-2 ring-gray-300 dark:ring-gray-600' 
                        : 'bg-' . $event['color'] . '-100 dark:bg-' . $event['color'] . '-900/20' }}">
                    <x-filament::icon
                        :icon="$event['icon']"
                        class="h-5 w-5 {{ isset($event['future']) && $event['future'] 
                            ? 'text-gray-500 dark:text-gray-400' 
                            : 'text-' . $event['color'] . '-600 dark:text-' . $event['color'] . '-400' }}"
                    />
                </div>
                
                <!-- Content -->
                <div class="flex-1 pt-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ $event['title'] }}
                        </h3>
                        <time class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $event['date']->format('d.m.Y H:i') }}
                            @if($event['date']->isToday())
                                <span class="text-primary-600 dark:text-primary-400">(Heute)</span>
                            @elseif($event['date']->isYesterday())
                                <span class="text-gray-600 dark:text-gray-400">(Gestern)</span>
                            @elseif($event['date']->isTomorrow())
                                <span class="text-warning-600 dark:text-warning-400">(Morgen)</span>
                            @endif
                        </time>
                    </div>
                    @if($event['description'])
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $event['description'] }}
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    @if(empty($events))
        <div class="text-center py-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Keine Ereignisse vorhanden
            </p>
        </div>
    @endif
</div>