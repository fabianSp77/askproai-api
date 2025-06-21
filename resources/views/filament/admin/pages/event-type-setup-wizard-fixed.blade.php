<x-filament-panels::page>
    @if($this->eventType)
        <div class="space-y-6">
            {{-- Progress Overview --}}
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold leading-6 text-gray-950 dark:text-white">
                                {{ $this->eventType->name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Setup-Fortschritt: {{ $this->eventType->getSetupProgress() }}%
                            </p>
                        </div>
                        <div class="text-right">
                            @php
                                $statusClasses = match($this->eventType->setup_status) {
                                    'complete' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20',
                                    'partial' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20',
                                    default => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/20'
                                };
                                $statusText = match($this->eventType->setup_status) {
                                    'complete' => 'Vollständig konfiguriert',
                                    'partial' => 'Teilweise konfiguriert',
                                    default => 'Unvollständig'
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClasses }}">
                                {{ $statusText }}
                            </span>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="relative">
                        <div class="overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-2 rounded-full bg-primary-600 transition-all duration-300 ease-out"
                                 style="width: {{ $this->eventType->getSetupProgress() }}%"></div>
                        </div>
                    </div>

                    {{-- Checklist Summary --}}
                    @if($this->checklist)
                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach($this->checklist as $key => $item)
                                <div class="flex items-center space-x-3">
                                    @if($item['completed'] ?? false)
                                        <x-filament::icon
                                            icon="heroicon-s-check-circle"
                                            class="h-5 w-5 text-success-500"
                                        />
                                    @else
                                        <x-filament::icon
                                            icon="heroicon-o-x-circle"
                                            class="h-5 w-5 text-gray-400"
                                        />
                                    @endif
                                    <span class="text-sm {{ ($item['completed'] ?? false) ? 'text-gray-950 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $item['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Cal.com Links --}}
            @if(!empty($this->calcomLinks))
                <div class="fi-section rounded-xl bg-primary-50 shadow-sm ring-1 ring-primary-200 dark:bg-primary-400/10 dark:ring-primary-400/20">
                    <div class="fi-section-content p-6">
                        <h4 class="flex items-center text-base font-semibold leading-6 text-primary-950 dark:text-primary-400">
                            <x-filament::icon
                                icon="heroicon-o-arrow-top-right-on-square"
                                class="mr-2 h-5 w-5"
                            />
                            Direkte Cal.com Einstellungen
                        </h4>
                        <p class="mt-2 text-sm text-primary-700 dark:text-primary-300">
                            Einige Einstellungen müssen direkt in Cal.com vorgenommen werden. 
                            Nutzen Sie diese Links für direkten Zugriff:
                        </p>
                        
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach($this->calcomLinks as $section => $linkData)
                                @if($linkData['success'] ?? false)
                                    <a href="{{ $linkData['url'] }}" 
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="flex items-center justify-between rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition-shadow hover:shadow-md dark:bg-gray-800 dark:ring-white/10">
                                        <div class="flex items-center space-x-3">
                                            <div class="rounded-lg bg-primary-100 p-2 dark:bg-primary-400/20">
                                                @php
                                                    $icon = match($section) {
                                                        'availability' => 'heroicon-o-calendar-days',
                                                        'advanced' => 'heroicon-o-cog-6-tooth',
                                                        'workflows' => 'heroicon-o-bell-alert',
                                                        'webhooks' => 'heroicon-o-link',
                                                        default => 'heroicon-o-arrow-right'
                                                    };
                                                @endphp
                                                <x-filament::icon
                                                    :icon="$icon"
                                                    class="h-5 w-5 text-primary-600 dark:text-primary-400"
                                                />
                                            </div>
                                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                                {{ $linkData['section_name'] ?? ucfirst($section) }}
                                            </span>
                                        </div>
                                        <x-filament::icon
                                            icon="heroicon-o-arrow-right"
                                            class="h-5 w-5 text-gray-400"
                                        />
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main Form --}}
            <form wire:submit.prevent="saveSettings">
                {{ $this->form }}
                
                <div class="mt-6 flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        Einstellungen speichern
                    </x-filament::button>
                </div>
            </form>
        </div>
    @else
        {{-- Selection Form --}}
        <div class="space-y-6">
            {{ $this->form }}
            
            <div class="flex justify-end">
                <x-filament::button 
                    wire:click="selectEventType"
                    :disabled="!$this->selectedEventTypeId"
                    icon="heroicon-o-arrow-right">
                    Event-Type konfigurieren
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>