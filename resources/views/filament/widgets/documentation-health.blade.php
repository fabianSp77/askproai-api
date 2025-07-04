<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $data = $this->getHealthData();
            $score = $data['health_score'];
            $color = match(true) {
                $score >= 80 => 'success',
                $score >= 60 => 'warning',
                $score >= 40 => 'danger',
                default => 'danger'
            };
        @endphp
        
        <div class="space-y-4">
            {{-- Header mit Score --}}
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold">📚 Dokumentations-Gesundheit</h2>
                
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-3xl font-bold text-{{ $color }}-600">
                            {{ $score }}%
                        </div>
                        <div class="text-sm text-gray-500">
                            Letzte Prüfung: {{ \Carbon\Carbon::parse($data['last_check'])->diffForHumans() }}
                        </div>
                    </div>
                    
                    <x-filament::button
                        wire:click="refreshHealth"
                        size="sm"
                        icon="heroicon-o-arrow-path"
                    >
                        Aktualisieren
                    </x-filament::button>
                </div>
            </div>
            
            {{-- Progress Bar --}}
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div class="bg-{{ $color }}-600 h-2.5 rounded-full" style="width: {{ $score }}%"></div>
            </div>
            
            {{-- Status Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Veraltete Dokumente --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-orange-500" />
                        <h3 class="font-semibold">Veraltete Dokumente</h3>
                    </div>
                    <div class="text-2xl font-bold mt-2">
                        {{ count($data['outdated_docs']) }}
                    </div>
                    @if(count($data['outdated_docs']) > 0)
                        <div class="mt-2 text-sm text-gray-600">
                            @foreach(array_slice($data['outdated_docs'], 0, 3) as $doc)
                                <div>• {{ $doc }}</div>
                            @endforeach
                            @if(count($data['outdated_docs']) > 3)
                                <div class="text-gray-400">... und {{ count($data['outdated_docs']) - 3 }} weitere</div>
                            @endif
                        </div>
                    @endif
                </div>
                
                {{-- Defekte Links --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-link class="w-5 h-5 text-red-500" />
                        <h3 class="font-semibold">Defekte Links</h3>
                    </div>
                    <div class="text-2xl font-bold mt-2">
                        {{ count($data['broken_links']) }}
                    </div>
                    @if(count($data['broken_links']) > 0)
                        <div class="mt-2 text-sm text-gray-600">
                            @foreach(array_slice($data['broken_links'], 0, 3) as $link)
                                <div>• {{ $link }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                {{-- Empfehlungen --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-light-bulb class="w-5 h-5 text-blue-500" />
                        <h3 class="font-semibold">Empfehlungen</h3>
                    </div>
                    <div class="text-2xl font-bold mt-2">
                        {{ count($data['suggestions']) }}
                    </div>
                    @if(count($data['suggestions']) > 0)
                        <div class="mt-2 text-sm text-gray-600">
                            @foreach(array_slice($data['suggestions'], 0, 2) as $suggestion)
                                <div>• {{ $suggestion }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Quick Actions --}}
            <div class="flex gap-2 pt-4 border-t">
                <x-filament::button
                    href="{{ url('/docs') }}"
                    tag="a"
                    target="_blank"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-book-open"
                >
                    Dokumentation öffnen
                </x-filament::button>
                
                <x-filament::button
                    x-on:click="$clipboard('php artisan docs:check-updates')"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-clipboard"
                >
                    Befehl kopieren
                </x-filament::button>
                
                @if($score < 70)
                    <x-filament::button
                        x-on:click="$clipboard('php artisan docs:check-updates --auto-fix')"
                        size="sm"
                        color="warning"
                        icon="heroicon-o-wrench"
                    >
                        Auto-Fix Befehl
                    </x-filament::button>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>