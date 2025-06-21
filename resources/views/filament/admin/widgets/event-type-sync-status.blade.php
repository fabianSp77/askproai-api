<x-filament-widgets::widget>
    @php
        $syncData = $this->getSyncData();
        $statusColors = [
            'success' => 'success',
            'warning' => 'warning',
            'error' => 'danger'
        ];
        $statusIcons = [
            'success' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'error' => 'heroicon-o-x-circle'
        ];
    @endphp
    
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <x-filament::icon 
                        :icon="$statusIcons[$syncData['status']]"
                        class="h-6 w-6 text-{{ $statusColors[$syncData['status']] }}-500"
                    />
                    <div>
                        <h3 class="text-lg font-medium">Event Type Synchronisation</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $syncData['message'] }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Letzte Sync:</span>
                        <span class="font-medium">{{ $syncData['lastSync'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Event Types:</span>
                        <span class="font-medium">{{ $syncData['totalEventTypes'] }}</span>
                    </div>
                    @if($syncData['failedEventTypes'] > 0)
                        <div>
                            <span class="text-danger-500">Fehler:</span>
                            <span class="font-medium text-danger-600">{{ $syncData['failedEventTypes'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            @if($syncData['canSync'])
                <x-filament::button
                    wire:click="syncNow"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-arrow-path"
                    size="sm"
                >
                    <span wire:loading.remove>Jetzt synchronisieren</span>
                    <span wire:loading>Synchronisiere...</span>
                </x-filament::button>
            @else
                <x-filament::button
                    disabled
                    icon="heroicon-o-key"
                    size="sm"
                    color="gray"
                >
                    API Key fehlt
                </x-filament::button>
            @endif
        </div>
        
        @if($syncData['totalEventTypes'] > 0)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-4">
                    <div class="flex-1">
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-success-500 transition-all duration-300"
                                style="width: {{ $syncData['totalEventTypes'] > 0 ? round(($syncData['syncedEventTypes'] / $syncData['totalEventTypes']) * 100) : 0 }}%"
                            ></div>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $syncData['syncedEventTypes'] }} von {{ $syncData['totalEventTypes'] }} synchronisiert
                    </span>
                </div>
            </div>
        @endif
    </x-filament::section>
    
    @push('scripts')
    <script>
        window.addEventListener('notify', event => {
            new FilamentNotification()
                .title(event.detail.message)
                .status(event.detail.type)
                .send();
        });
    </script>
    @endpush
</x-filament-widgets::widget>