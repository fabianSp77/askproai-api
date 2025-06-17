<div class="space-y-4">
    <div class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-2">
        Verlauf des Anrufs
    </div>
    
    <div class="relative">
        <!-- Timeline Line -->
        <div class="absolute left-4 top-8 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
        
        <!-- Timeline Events -->
        <div class="space-y-6">
            <!-- Start -->
            <div class="flex items-start">
                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-m-phone-arrow-down-left class="w-4 h-4 text-green-600 dark:text-green-400" />
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Anruf gestartet
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $startTime }} Uhr • Von: {{ $fromNumber }}
                    </div>
                </div>
            </div>
            
            <!-- AI Greeting -->
            @if($hasTranscript)
            <div class="flex items-start">
                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-m-sparkles class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        KI-Agent begrüßt Anrufer
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Automatische Begrüßung und Gesprächsführung
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Intent Detection -->
            @if($intent)
            <div class="flex items-start">
                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-amber-100 dark:bg-amber-900 rounded-full">
                    <x-heroicon-m-light-bulb class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Anliegen erkannt
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $intent }}
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Customer Info -->
            @if($customerIdentified)
            <div class="flex items-start">
                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <x-heroicon-m-user class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Kunde identifiziert
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $customerName ?? 'Name erfasst' }}
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Appointment Request -->
            @if($appointmentRequested)
            <div class="flex items-start">
                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-full">
                    <x-heroicon-m-calendar class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Terminwunsch geäußert
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $requestedDate ?? 'Datum besprochen' }}
                    </div>
                </div>
            </div>
            @endif
            
            <!-- Appointment Booked -->
            @if($appointmentBooked)
            <div class="flex items-start">
                <div class="relative z-10 flex items-center justify-center w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-m-check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Termin erfolgreich gebucht
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $appointmentDate }}
                    </div>
                </div>
            </div>
            @endif
            
            <!-- End -->
            <div class="flex items-start">
                <div class="relative z-10 flex items-center justify-center w-8 h-8 {{ $callSuccessful ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }} rounded-full">
                    <x-heroicon-m-phone-x-mark class="w-4 h-4 {{ $callSuccessful ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Anruf beendet
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $endTime }} Uhr • Dauer: {{ $duration }} • {{ $disconnectionReason }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Stats -->
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ $duration }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Gesprächsdauer
                </div>
            </div>
            <div>
                <div class="text-lg font-bold {{ $sentiment === 'positive' ? 'text-green-600' : ($sentiment === 'negative' ? 'text-red-600' : 'text-gray-600') }}">
                    {{ match($sentiment) {
                        'positive' => '😊',
                        'negative' => '😞',
                        'neutral' => '😐',
                        default => '—'
                    } }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Stimmung
                </div>
            </div>
            <div>
                <div class="text-lg font-bold {{ $appointmentBooked ? 'text-green-600' : 'text-amber-600' }}">
                    {{ $appointmentBooked ? '✅' : '⏳' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $appointmentBooked ? 'Termin gebucht' : 'Offen' }}
                </div>
            </div>
        </div>
    </div>
</div>