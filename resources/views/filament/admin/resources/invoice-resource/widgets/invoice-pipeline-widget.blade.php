<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $data = $this->getPipelineData();
        @endphp
        
        <div class="space-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Rechnungs-Pipeline
                </h2>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Potentieller Umsatz: <span class="font-semibold text-gray-900 dark:text-white">€ {{ number_format($data['potential_revenue'], 2, ',', '.') }}</span>
                </div>
            </div>
            
            {{-- Pipeline Stages --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($data['pipeline'] as $index => $stage)
                    <div class="relative">
                        {{-- Stage Card --}}
                        @php
                            $borderClass = match($stage['color']) {
                                'gray' => 'border-gray-200 dark:border-gray-700',
                                'warning' => 'border-amber-200 dark:border-amber-700',
                                'success' => 'border-green-200 dark:border-green-700',
                                'danger' => 'border-red-200 dark:border-red-700',
                                default => 'border-gray-200 dark:border-gray-700',
                            };
                            $iconColorClass = match($stage['color']) {
                                'gray' => 'text-gray-600 dark:text-gray-400',
                                'warning' => 'text-amber-600 dark:text-amber-400',
                                'success' => 'text-green-600 dark:text-green-400',
                                'danger' => 'text-red-600 dark:text-red-400',
                                default => 'text-gray-600 dark:text-gray-400',
                            };
                            $amountColorClass = match($stage['color']) {
                                'gray' => 'text-gray-600 dark:text-gray-400',
                                'warning' => 'text-amber-600 dark:text-amber-400',
                                'success' => 'text-green-600 dark:text-green-400',
                                'danger' => 'text-red-600 dark:text-red-400',
                                default => 'text-gray-600 dark:text-gray-400',
                            };
                        @endphp
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border-2 {{ $borderClass }}">
                            <div class="flex items-center justify-between mb-2">
                                <x-dynamic-component 
                                    :component="'heroicon-o-' . str_replace('heroicon-o-', '', $stage['icon'])"
                                    class="w-5 h-5 {{ $iconColorClass }}"
                                />
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ $stage['percentage'] }}%
                                </span>
                            </div>
                            
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                {{ $stage['name'] }}
                            </h3>
                            
                            <div class="space-y-1">
                                <p class="text-2xl font-bold {{ $amountColorClass }}">
                                    € {{ number_format($stage['amount'], 2, ',', '.') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $stage['count'] }} {{ $stage['count'] === 1 ? 'Rechnung' : 'Rechnungen' }}
                                </p>
                            </div>
                        </div>
                        
                        {{-- Arrow between stages --}}
                        @if($index < count($data['pipeline']) - 1)
                            <div class="hidden md:block absolute top-1/2 -right-2 transform -translate-y-1/2 z-10">
                                <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            {{-- Cancelled Status (separate) --}}
            @if($data['cancelled']['count'] > 0)
                <div class="mt-4">
                    @php
                        $stage = $data['cancelled'];
                        $borderClass = 'border-red-200 dark:border-red-700';
                        $iconColorClass = 'text-red-600 dark:text-red-400';
                        $amountColorClass = 'text-red-600 dark:text-red-400';
                    @endphp
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border-2 {{ $borderClass }} opacity-75">
                        <div class="flex items-center justify-between mb-2">
                            <x-dynamic-component 
                                :component="'heroicon-o-' . str_replace('heroicon-o-', '', $stage['icon'])"
                                class="w-5 h-5 {{ $iconColorClass }}"
                            />
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ $stage['percentage'] }}%
                            </span>
                        </div>
                        
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                            {{ $stage['name'] }}
                        </h3>
                        
                        <div class="space-y-1">
                            <p class="text-2xl font-bold {{ $amountColorClass }}">
                                € {{ number_format($stage['amount'], 2, ',', '.') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $stage['count'] }} {{ $stage['count'] === 1 ? 'Rechnung' : 'Rechnungen' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Conversion Metrics --}}
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                    Conversion-Raten
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <div class="flex items-center space-x-2">
                            <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Entwurf → Finalisiert
                            </span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $data['conversions']['draft_to_open'] }}%
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <div class="flex items-center space-x-2">
                            <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Offen → Bezahlt
                            </span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $data['conversions']['open_to_paid'] }}%
                        </span>
                    </div>
                </div>
                
                {{-- Processing Times --}}
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 text-center">
                    Durchschnittliche Bearbeitungszeiten: 
                    Finalisierung {{ $data['processing_times']['draft_to_open'] }} | 
                    Zahlung {{ $data['processing_times']['open_to_paid'] }}
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>