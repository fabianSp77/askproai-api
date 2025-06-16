<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            {{-- Progress Overview --}}
            <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                            Ihr Fortschritt
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            @if($progress['is_completed'])
                                Einrichtung abgeschlossen! Ihr System ist einsatzbereit.
                            @else
                                Noch {{ 100 - $progress['progress_percentage'] }}% bis zur vollständigen Einrichtung
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-primary-600">
                            {{ $progress['progress_percentage'] }}%
                        </div>
                        <div class="text-sm text-gray-500">
                            Bereitschaftswert: {{ $readinessScore }}%
                        </div>
                    </div>
                </div>
                
                {{-- Progress Bar --}}
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="bg-primary-600 h-4 rounded-full transition-all duration-500 ease-out relative" 
                         style="width: {{ $progress['progress_percentage'] }}%">
                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                </div>
                
                @if(!$progress['is_completed'])
                    <div class="mt-4 flex justify-center">
                        <a href="{{ route('filament.admin.pages.onboarding') }}" 
                           class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                            <x-heroicon-o-arrow-right class="w-5 h-5 mr-2" />
                            Einrichtung fortsetzen
                        </a>
                    </div>
                @endif
            </div>

            {{-- Quick Checklist --}}
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5 mr-2 text-primary-600" />
                        Schnell-Checkliste
                    </h3>
                    <div class="space-y-2">
                        @foreach(array_slice($checklist['items'], 0, 5) as $key => $item)
                            <label class="flex items-start space-x-3 cursor-pointer group">
                                <input type="checkbox" 
                                       class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                       {{ in_array($key, $checklist['completed_items']) ? 'checked' : '' }}
                                       wire:click="$emit('updateChecklistItem', '{{ $key }}')"
                                       disabled>
                                <span class="text-sm {{ in_array($key, $checklist['completed_items']) ? 'text-gray-500 line-through' : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ $item }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Next Steps --}}
                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <x-heroicon-o-light-bulb class="w-5 h-5 mr-2 text-amber-500" />
                        Empfohlene nächste Schritte
                    </h3>
                    <div class="space-y-3">
                        @forelse($nextSteps as $step)
                            <a href="{{ route($step['action']) }}" 
                               class="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition group">
                                <div class="flex items-start">
                                    <x-dynamic-component :component="$step['icon']" class="w-5 h-5 text-gray-400 mt-0.5 mr-3" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-primary-600">
                                            {{ $step['title'] }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $step['description'] }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">
                                Großartig! Sie haben alle wichtigen Schritte abgeschlossen.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Achievements --}}
            @if($achievements->count() > 0)
                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <x-heroicon-o-trophy class="w-5 h-5 mr-2 text-yellow-500" />
                        Ihre Erfolge
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($achievements->take(8) as $achievement)
                            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <x-dynamic-component :component="$achievement->icon" 
                                                   class="w-8 h-8 mx-auto mb-2 text-{{ $achievement->type === 'milestone' ? 'primary' : ($achievement->type === 'badge' ? 'green' : 'yellow') }}-500" />
                                <div class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ $achievement->name }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    +{{ $achievement->points }} Punkte
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>