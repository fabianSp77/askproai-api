<x-filament-widgets::widget>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Insights & Aktionen</h3>
                    @if($hasUrgentIssues)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200 animate-pulse">
                            <span class="w-1.5 h-1.5 bg-red-600 rounded-full mr-1.5"></span>
                            Dringend
                        </span>
                    @endif
                </div>
                <span class="text-xs text-gray-500">Auto-Update: 30s</span>
            </div>
        </div>
        
        {{-- Insights Section --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
            @forelse($insights as $insight)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200 group">
                    <div class="flex items-start gap-4">
                        {{-- Icon with animated background --}}
                        <div class="flex-shrink-0">
                            <div class="p-2.5 rounded-xl bg-{{ $insight['color'] }}-100 dark:bg-{{ $insight['color'] }}-900/20 group-hover:scale-110 transition-transform">
                                <x-dynamic-component 
                                    :component="$insight['icon']" 
                                    class="w-5 h-5 text-{{ $insight['color'] }}-600 dark:text-{{ $insight['color'] }}-400"
                                />
                            </div>
                        </div>
                        
                        {{-- Content --}}
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div class="space-y-1">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $insight['title'] }}
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $insight['message'] }}
                                    </p>
                                </div>
                                
                                {{-- Priority Badge with better styling --}}
                                @php
                                    $priorityConfig = [
                                        'urgent' => ['bg' => 'red', 'text' => 'Sofort', 'animate' => true],
                                        'high' => ['bg' => 'orange', 'text' => 'Hoch', 'animate' => false],
                                        'medium' => ['bg' => 'yellow', 'text' => 'Mittel', 'animate' => false],
                                        'low' => ['bg' => 'gray', 'text' => 'Niedrig', 'animate' => false]
                                    ];
                                    $config = $priorityConfig[$insight['priority']] ?? $priorityConfig['low'];
                                @endphp
                                <span class="ml-4 inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-{{ $config['bg'] }}-100 text-{{ $config['bg'] }}-800 dark:bg-{{ $config['bg'] }}-900/20 dark:text-{{ $config['bg'] }}-200 {{ $config['animate'] ? 'animate-pulse' : '' }}">
                                    {{ $config['text'] }}
                                </span>
                            </div>
                            
                            {{-- Action Button with better hover state --}}
                            @if($insight['action'] && $insight['actionUrl'])
                                <div class="mt-3">
                                    <a href="{{ $insight['actionUrl'] }}" 
                                       class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/30 transition-all group/link">
                                        {{ $insight['action'] }}
                                        <x-heroicon-o-arrow-right class="w-4 h-4 group-hover/link:translate-x-1 transition-transform" />
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center mb-4">
                        <x-heroicon-o-check-circle class="w-10 h-10 text-green-600 dark:text-green-400" />
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">
                        Alles läuft optimal
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Keine Handlungsempfehlungen vorhanden
                    </p>
                </div>
            @endforelse
        </div>
        
        {{-- Quick Actions Footer with modern card design --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Schnellzugriff
            </h4>
            <div class="grid grid-cols-2 gap-3">
                @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}" 
                       class="group relative flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-{{ $action['color'] }}-300 dark:hover:border-{{ $action['color'] }}-700 hover:shadow-md transition-all duration-200">
                        <div class="p-2 rounded-lg bg-{{ $action['color'] }}-50 dark:bg-{{ $action['color'] }}-900/20 group-hover:bg-{{ $action['color'] }}-100 dark:group-hover:bg-{{ $action['color'] }}-900/30 transition-colors">
                            <x-dynamic-component 
                                :component="$action['icon']" 
                                class="w-5 h-5 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400"
                            />
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-{{ $action['color'] }}-600 dark:group-hover:text-{{ $action['color'] }}-400 transition-colors">
                            {{ $action['label'] }}
                        </span>
                        <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400 dark:text-gray-600 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" />
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>