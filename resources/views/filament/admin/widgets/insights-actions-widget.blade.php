<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    Insights & Aktionen
                    @if($hasUrgentIssues)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200">
                            Dringend
                        </span>
                    @endif
                </h3>
                <span class="text-xs text-gray-500">Auto-Update: 30s</span>
            </div>
        </div>
        
        {{-- Insights Section --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($insights as $insight)
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0">
                            <div class="p-2 rounded-lg bg-{{ $insight['color'] }}-100 dark:bg-{{ $insight['color'] }}-900/20">
                                <x-dynamic-component 
                                    :component="$insight['icon']" 
                                    class="w-5 h-5 text-{{ $insight['color'] }}-600 dark:text-{{ $insight['color'] }}-400"
                                />
                            </div>
                        </div>
                        
                        {{-- Content --}}
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $insight['title'] }}
                                    </h4>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $insight['message'] }}
                                    </p>
                                </div>
                                
                                {{-- Priority Badge --}}
                                @php
                                    $priorityColors = [
                                        'urgent' => 'red',
                                        'high' => 'orange',
                                        'medium' => 'yellow',
                                        'low' => 'gray'
                                    ];
                                    $priorityLabels = [
                                        'urgent' => 'Sofort',
                                        'high' => 'Hoch',
                                        'medium' => 'Mittel',
                                        'low' => 'Niedrig'
                                    ];
                                @endphp
                                <span class="ml-4 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $priorityColors[$insight['priority']] ?? 'gray' }}-100 text-{{ $priorityColors[$insight['priority']] ?? 'gray' }}-800 dark:bg-{{ $priorityColors[$insight['priority']] ?? 'gray' }}-900/20 dark:text-{{ $priorityColors[$insight['priority']] ?? 'gray' }}-200">
                                    {{ $priorityLabels[$insight['priority']] ?? 'Info' }}
                                </span>
                            </div>
                            
                            {{-- Action Button --}}
                            @if($insight['action'] && $insight['actionUrl'])
                                <div class="mt-3">
                                    <a href="{{ $insight['actionUrl'] }}" 
                                       class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                        {{ $insight['action'] }}
                                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <x-heroicon-o-check-circle class="mx-auto h-12 w-12 text-green-400" />
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        Alles läuft optimal
                    </p>
                    <p class="text-sm text-gray-500">
                        Keine Handlungsempfehlungen
                    </p>
                </div>
            @endforelse
        </div>
        
        {{-- Quick Actions Footer --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Schnellzugriff
            </h4>
            <div class="grid grid-cols-2 gap-2">
                @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}" 
                       class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 transition-colors group">
                        <x-dynamic-component 
                            :component="$action['icon']" 
                            class="w-4 h-4 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400 group-hover:scale-110 transition-transform"
                        />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $action['label'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>