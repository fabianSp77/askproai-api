<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-4">📚 AskProAI Dokumentations-Zentrale</h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Zentrale Anlaufstelle für alle Dokumentationen, Prozesse und Entwickler-Ressourcen.
                        Das System überwacht automatisch Code-Änderungen und informiert über notwendige Dokumentations-Updates.
                    </p>
                </div>
                <x-filament::button
                    href="/mkdocs/"
                    tag="a"
                    target="_blank"
                    color="primary"
                    icon="heroicon-o-arrow-top-right-on-square"
                    size="lg"
                >
                    Zur vollständigen Dokumentation
                </x-filament::button>
            </div>
        </div>

        {{-- Documentation Health Widget --}}
        @livewire(\App\Filament\Admin\Widgets\DocumentationHealthWidget::class)

        {{-- Main Documentation --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Hauptdokumentation</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($documentationLinks as $doc)
                    <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h4 class="font-semibold text-lg mb-2">{{ $doc['title'] }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $doc['description'] }}</p>
                        @if($doc['internal'])
                            <x-filament::button
                                href="{{ $doc['url'] }}"
                                tag="a"
                                target="_blank"
                                size="sm"
                                color="primary"
                                icon="heroicon-o-arrow-top-right-on-square"
                            >
                                Öffnen
                            </x-filament::button>
                        @else
                            <x-filament::button
                                href="{{ $doc['url'] }}"
                                tag="a"
                                target="_blank"
                                size="sm"
                                color="gray"
                                icon="heroicon-o-arrow-top-right-on-square"
                            >
                                Extern öffnen
                            </x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Process Documentation --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Prozess-Dokumentation</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($processLinks as $doc)
                    <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <h4 class="font-semibold text-lg mb-2">{{ $doc['title'] }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ $doc['description'] }}</p>
                        <x-filament::button
                            href="{{ $doc['url'] }}"
                            tag="a"
                            target="_blank"
                            size="sm"
                            color="primary"
                            icon="heroicon-o-arrow-top-right-on-square"
                        >
                            Öffnen
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Commands --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Quick Commands</h3>
            <div class="space-y-3">
                @foreach($quickCommands as $cmd)
                    <div class="flex items-center justify-between p-3 border dark:border-gray-700 rounded-lg">
                        <div class="flex-1">
                            <h4 class="font-medium">{{ $cmd['label'] }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $cmd['description'] }}</p>
                            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $cmd['command'] }}</code>
                        </div>
                        <div class="ml-4 flex gap-2">
                            <x-filament::button
                                x-on:click="$clipboard('{{ $cmd['command'] }}')"
                                size="sm"
                                color="gray"
                                icon="heroicon-o-clipboard"
                            >
                                Kopieren
                            </x-filament::button>
                            <x-filament::button
                                wire:click="runCommand('{{ $cmd['command'] }}')"
                                size="sm"
                                color="primary"
                                icon="heroicon-o-play"
                            >
                                Ausführen
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Weitere Dokumentationen --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Weitere Dokumentationen</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="/mkdocs/architecture/overview/" 
                   class="block p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold">🏗️ Architektur Übersicht</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        System-Architektur und Design-Entscheidungen
                    </p>
                </a>
                
                <a href="/mkdocs/architecture/database-schema/" 
                   class="block p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold">🗄️ Datenbank Schema</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Tabellen, Relationen und Migrationen
                    </p>
                </a>
                
                <a href="/mkdocs/features/appointment-booking/" 
                   class="block p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold">📅 Appointment Booking</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Terminbuchungs-System Dokumentation
                    </p>
                </a>
                
                <a href="/mkdocs/features/multi-tenancy/" 
                   class="block p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold">🏢 Multi-Tenancy</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Mandantenfähigkeit und Datenisolierung
                    </p>
                </a>
            </div>
        </div>

        {{-- Auto-Update Info --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600" />
                Automatische Dokumentations-Updates
            </h3>
            <div class="space-y-2 text-sm">
                <p>Das System erkennt automatisch wenn Dokumentation aktualisiert werden muss bei:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Neuen Features (feat: Commits)</li>
                    <li>Service-Änderungen (app/Services/)</li>
                    <li>MCP-Server Updates (app/Services/MCP/)</li>
                    <li>API-Änderungen (routes/, Controller)</li>
                    <li>Datenbank-Änderungen (Migrations)</li>
                </ul>
                <p class="mt-3">
                    Git Hooks sind aktiv und prüfen bei jedem Commit. 
                    Bei kritisch veralteter Dokumentation (&lt;50% Health) wird der Push blockiert.
                </p>
            </div>
        </div>

        {{-- External Resources --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Externe Ressourcen</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="https://docs.anthropic.com/en/docs/claude-code" 
                   target="_blank"
                   class="block p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold flex items-center gap-2">
                        <x-heroicon-o-code-bracket class="w-5 h-5" />
                        Claude Code Docs
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Offizielle Dokumentation für Claude Code
                    </p>
                </a>
                
                <a href="https://docs.retellai.com" 
                   target="_blank"
                   class="block p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold flex items-center gap-2">
                        <x-heroicon-o-phone class="w-5 h-5" />
                        Retell.ai Docs
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        AI Phone Service Dokumentation
                    </p>
                </a>
                
                <a href="https://cal.com/docs" 
                   target="_blank"
                   class="block p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <h4 class="font-semibold flex items-center gap-2">
                        <x-heroicon-o-calendar class="w-5 h-5" />
                        Cal.com Docs
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Kalender-Integration Dokumentation
                    </p>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>