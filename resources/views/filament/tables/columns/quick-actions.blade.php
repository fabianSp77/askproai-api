@php
    $hasRecording = !empty($getRecord()->audio_url);
    $hasAppointment = !empty($getRecord()->appointment_id);
    $hasCustomer = !empty($getRecord()->customer_id);
    $canCreateAppointment = $hasCustomer && !$hasAppointment;
@endphp

<div class="flex items-center gap-1" x-data="{ open: false }">
    <!-- Play recording -->
    @if($hasRecording)
        <button type="button"
                onclick="window.playCallRecording('{{ $getRecord()->id }}', '{{ $getRecord()->audio_url }}')"
                class="p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:text-gray-400 dark:hover:text-primary-400 dark:hover:bg-primary-900/20 transition-all duration-200"
                title="Aufnahme abspielen">
            <x-heroicon-m-play-circle class="w-4 h-4" />
        </button>
    @endif
    
    <!-- Create appointment -->
    @if($canCreateAppointment)
        <button type="button"
                wire:click="mountTableAction('create_appointment', '{{ $getRecord()->id }}')"
                class="p-1.5 rounded-lg text-gray-500 hover:text-success-600 hover:bg-success-50 dark:text-gray-400 dark:hover:text-success-400 dark:hover:bg-success-900/20 transition-all duration-200"
                title="Termin erstellen">
            <x-heroicon-m-calendar-days class="w-4 h-4" />
        </button>
    @endif
    
    <!-- View details -->
    <a href="{{ \App\Filament\Admin\Resources\CallResource::getUrl('view', ['record' => $getRecord()]) }}"
       class="p-1.5 rounded-lg text-gray-500 hover:text-info-600 hover:bg-info-50 dark:text-gray-400 dark:hover:text-info-400 dark:hover:bg-info-900/20 transition-all duration-200"
       title="Details anzeigen">
        <x-heroicon-m-eye class="w-4 h-4" />
    </a>
    
    <!-- More actions dropdown -->
    <div class="relative">
        <button type="button"
                @click="open = !open"
                @click.away="open = false"
                class="p-1.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-800 transition-all duration-200"
                title="Weitere Aktionen">
            <x-heroicon-m-ellipsis-vertical class="w-4 h-4" />
        </button>
        
        <div x-show="open"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 z-10 mt-1 w-48 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
             style="display: none;">
            <div class="py-1">
                <!-- Share -->
                <button type="button"
                        wire:click="mountTableAction('share_call', '{{ $getRecord()->id }}')"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                    <x-heroicon-m-share class="w-4 h-4" />
                    Teilen
                </button>
                
                <!-- Email -->
                <button type="button"
                        wire:click="mountTableAction('send_email', '{{ $getRecord()->id }}')"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                    <x-heroicon-m-envelope class="w-4 h-4" />
                    Per E-Mail senden
                </button>
                
                <!-- Analyze -->
                <button type="button"
                        wire:click="analyzeCall('{{ $getRecord()->id }}')"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                    <x-heroicon-m-sparkles class="w-4 h-4" />
                    KI-Analyse
                </button>
                
                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                
                <!-- Archive -->
                <button type="button"
                        wire:click="archiveCall('{{ $getRecord()->id }}')"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2">
                    <x-heroicon-m-archive-box class="w-4 h-4" />
                    Archivieren
                </button>
                
                <!-- Delete -->
                <button type="button"
                        wire:click="mountTableAction('delete', '{{ $getRecord()->id }}')"
                        @click="open = false"
                        class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                    <x-heroicon-m-trash class="w-4 h-4" />
                    Löschen
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.playCallRecording = function(callId, audioUrl) {
        // Create a mini player or dispatch event to play audio
        const event = new CustomEvent('play-call-recording', {
            detail: { callId, audioUrl }
        });
        window.dispatchEvent(event);
    };
</script>