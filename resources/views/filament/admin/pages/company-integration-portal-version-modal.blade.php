{{-- Agent Version History Modal --}}
@if($showVersionModal && $selectedAgentVersions)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div 
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                wire:click="closeVersionModal"
            ></div>
            
            <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-3xl w-full">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                <x-heroicon-o-clock class="w-5 h-5" />
                                Agent Version History
                            </h3>
                            <p class="text-sm text-primary-100 mt-1">
                                {{ $selectedAgentName ?? 'Agent' }} - Verfügbare Versionen
                            </p>
                        </div>
                        <button 
                            wire:click="closeVersionModal"
                            class="text-white hover:text-primary-100 transition-colors"
                        >
                            <x-heroicon-m-x-mark class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                
                {{-- Version List --}}
                <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto">
                    @foreach($selectedAgentVersions as $version)
                        <div @class([
                            'relative p-4 rounded-lg border-2 transition-all duration-200',
                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $version['is_current'] ?? false,
                            'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' => !($version['is_current'] ?? false),
                        ])>
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $version['version_name'] ?? $version['version_id'] }}
                                        </h4>
                                        @if($version['is_current'] ?? false)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400">
                                                <x-heroicon-m-check-circle class="w-3 h-3 mr-1" />
                                                Aktuelle Version
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-calendar class="w-4 h-4" />
                                            <span>Erstellt: {{ \Carbon\Carbon::parse($version['created_at'])->format('d.m.Y H:i') }}</span>
                                        </div>
                                        
                                        @if($version['changes'] ?? null)
                                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                                <p class="font-medium mb-1">Änderungen:</p>
                                                <p class="text-gray-600 dark:text-gray-400">{{ $version['changes'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    {{-- Phone Number Assignments --}}
                                    @php
                                        $assignedPhones = [];
                                        foreach($phoneNumbers as $phone) {
                                            if(($phone['active_version'] ?? 'current') === $version['version_id'] && $phone['actual_agent_id'] === $selectedAgentId) {
                                                $assignedPhones[] = $phone;
                                            }
                                        }
                                    @endphp
                                    
                                    @if(count($assignedPhones) > 0)
                                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                                                Aktiv für {{ count($assignedPhones) }} Telefonnummer(n):
                                            </p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($assignedPhones as $phone)
                                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300">
                                                        <x-heroicon-m-phone class="w-3 h-3 mr-1" />
                                                        {{ $phone['formatted'] ?? $phone['number'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Actions --}}
                                <div class="ml-4 flex flex-col gap-2">
                                    <button 
                                        wire:click="viewVersionDetails('{{ $selectedAgentId }}', '{{ $version['version_id'] }}')"
                                        class="text-xs text-primary-600 hover:text-primary-700 font-medium whitespace-nowrap"
                                    >
                                        Details anzeigen
                                    </button>
                                    @if(!($version['is_current'] ?? false))
                                        <button 
                                            wire:click="activateVersionForAllPhones('{{ $selectedAgentId }}', '{{ $version['version_id'] }}')"
                                            class="text-xs text-green-600 hover:text-green-700 font-medium whitespace-nowrap"
                                        >
                                            Für alle aktivieren
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Footer --}}
                <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <x-heroicon-m-information-circle class="w-4 h-4 inline-block mr-1" />
                            Versionen können pro Telefonnummer individuell zugewiesen werden.
                        </p>
                        <x-filament::button
                            wire:click="closeVersionModal"
                            color="gray"
                        >
                            Schließen
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif