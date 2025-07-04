<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Header mit Quick Links --}}
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-2">🚀 Quick Documentation Access</h2>
            <p class="opacity-90">Die wichtigsten Dokumentationen mit interaktiven Diagrammen - alles auf einen Blick!</p>
        </div>

        {{-- Kritische Business-Dokumente --}}
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                🔴 Kritische Business-Dokumente
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($criticalDocs as $doc)
                    <a href="{{ $doc['url'] }}" target="_blank" 
                       class="group relative bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-lg transition-all duration-200 p-6 border-2 border-transparent hover:border-{{ $doc['color'] }}-500">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-{{ $doc['color'] }}-100 dark:bg-{{ $doc['color'] }}-900 rounded-lg flex items-center justify-center">
                                    @if($doc['icon'] === 'rocket-launch')
                                        <x-heroicon-o-rocket-launch class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @elseif($doc['icon'] === 'fire')
                                        <x-heroicon-o-fire class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @elseif($doc['icon'] === 'magnifying-glass')
                                        <x-heroicon-o-magnifying-glass class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @else
                                        <x-heroicon-o-document-text class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-{{ $doc['color'] }}-600">
                                    {{ $doc['title'] }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $doc['description'] }}
                                </p>
                                <div class="mt-3 space-y-1">
                                    @foreach($doc['features'] as $feature)
                                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 mr-1" />
                                            {{ $feature }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-heroicon-m-arrow-top-right-on-square class="w-5 h-5 text-gray-400" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Prozess-Visualisierungen --}}
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                📊 Prozess-Visualisierungen mit Diagrammen
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($processDocs as $doc)
                    <a href="{{ $doc['url'] }}" target="_blank" 
                       class="group relative bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-lg transition-all duration-200 p-6 border-2 border-transparent hover:border-{{ $doc['color'] }}-500">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-{{ $doc['color'] }}-100 dark:bg-{{ $doc['color'] }}-900 rounded-lg flex items-center justify-center">
                                    @if($doc['icon'] === 'rocket-launch')
                                        <x-heroicon-o-rocket-launch class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @elseif($doc['icon'] === 'fire')
                                        <x-heroicon-o-fire class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @elseif($doc['icon'] === 'magnifying-glass')
                                        <x-heroicon-o-magnifying-glass class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @else
                                        <x-heroicon-o-document-text class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-{{ $doc['color'] }}-600">
                                    {{ $doc['title'] }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $doc['description'] }}
                                </p>
                                <div class="mt-3 space-y-1">
                                    @foreach($doc['features'] as $feature)
                                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <x-heroicon-m-chart-bar class="w-4 h-4 text-blue-500 mr-1" />
                                            {{ $feature }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-heroicon-m-arrow-top-right-on-square class="w-5 h-5 text-gray-400" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Technische Dokumentation --}}
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                🔧 Technische Dokumentation
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($technicalDocs as $doc)
                    <a href="{{ $doc['url'] }}" target="_blank" 
                       class="group bg-gray-50 dark:bg-gray-900 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center space-x-3">
                            @if($doc['icon'] === 'book-open')
                                <x-heroicon-o-book-open class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            @elseif($doc['icon'] === 'cube')
                                <x-heroicon-o-cube class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            @elseif($doc['icon'] === 'key')
                                <x-heroicon-o-key class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            @else
                                <x-heroicon-o-document-text class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            @endif
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $doc['title'] }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $doc['description'] }}</p>
                            </div>
                            <x-heroicon-m-chevron-right class="w-5 h-5 text-gray-400 ml-auto" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Quick Info Box --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex">
                <x-heroicon-m-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300">
                        Tipp: Alle Dokumentationen enthalten interaktive Mermaid-Diagramme!
                    </h4>
                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                        Die Diagramme visualisieren komplexe Prozesse und Datenflüsse. 
                        Nutze die Decision Trees im Troubleshooting für schnelle Problemlösungen.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>