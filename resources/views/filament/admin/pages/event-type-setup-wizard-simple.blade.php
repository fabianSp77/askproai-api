<x-filament-panels::page>
    @if($this->eventType)
        <div class="space-y-6">
            {{-- Progress Overview --}}
            <x-filament::section>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">{{ $this->eventType->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Setup-Fortschritt: {{ $this->eventType->getSetupProgress() }}%
                        </p>
                    </div>
                    <x-filament::badge
                        :color="match($this->eventType->setup_status) {
                            'complete' => 'success',
                            'partial' => 'warning',
                            default => 'danger'
                        }">
                        {{ match($this->eventType->setup_status) {
                            'complete' => 'Vollständig konfiguriert',
                            'partial' => 'Teilweise konfiguriert',
                            default => 'Unvollständig'
                        } }}
                    </x-filament::badge>
                </div>

                {{-- Progress Bar --}}
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-300"
                         style="width: {{ $this->eventType->getSetupProgress() }}%"></div>
                </div>

                {{-- Checklist --}}
                @if($this->checklist)
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($this->checklist as $key => $item)
                            <div class="flex items-center space-x-2">
                                <x-filament::icon
                                    :icon="($item['completed'] ?? false) ? 'heroicon-s-check-circle' : 'heroicon-o-x-circle'"
                                    :class="($item['completed'] ?? false) ? 'text-success-500' : 'text-gray-400'"
                                    class="w-5 h-5"
                                />
                                <span class="text-sm {{ ($item['completed'] ?? false) ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $item['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            {{-- Cal.com Links --}}
            @if(!empty($this->calcomLinks))
                <x-filament::section>
                    <x-slot name="heading">
                        Direkte Cal.com Einstellungen
                    </x-slot>
                    
                    <x-slot name="description">
                        Einige Einstellungen müssen direkt in Cal.com vorgenommen werden.
                    </x-slot>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($this->calcomLinks as $section => $linkData)
                            @if($linkData['success'] ?? false)
                                <a href="{{ $linkData['url'] }}" 
                                   target="_blank"
                                   class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                                    <div class="flex items-center space-x-3">
                                        <x-filament::icon
                                            :icon="match($section) {
                                                'availability' => 'heroicon-o-calendar-days',
                                                'advanced' => 'heroicon-o-cog-6-tooth',
                                                'workflows' => 'heroicon-o-bell-alert',
                                                'webhooks' => 'heroicon-o-link',
                                                default => 'heroicon-o-arrow-right'
                                            }"
                                            class="w-5 h-5 text-primary-600"
                                        />
                                        <span class="text-sm font-medium">
                                            {{ $linkData['section_name'] ?? ucfirst($section) }}
                                        </span>
                                    </div>
                                    <x-filament::icon
                                        icon="heroicon-o-arrow-right"
                                        class="w-5 h-5 text-gray-400"
                                    />
                                </a>
                            @endif
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            {{-- Configuration Form --}}
            {{ $this->form }}
        </div>
    @else
        {{-- Selection Form --}}
        {{ $this->form }}
    @endif
</x-filament-panels::page>