<x-filament-panels::page>
    {{-- Company Selector (for super admin) --}}
    @if(auth()->user()->hasRole('super_admin'))
        <div class="mb-6">
            <x-filament::section>
                <x-slot name="heading">
                    Firma auswählen
                </x-slot>

                <x-slot name="description">
                    Wählen Sie die Firma aus, deren Einstellungen Sie verwalten möchten.
                </x-slot>

                <div class="space-y-4">
                    <select
                        wire:model.live="selectedCompanyId"
                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        @foreach($this->getCompanyOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    @if($selectedCompanyId)
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <x-heroicon-o-information-circle class="w-4 h-4" />
                            <span>Verwalten Sie Einstellungen für: <strong>{{ $this->getCompanyOptions()[$selectedCompanyId] ?? 'Unbekannt' }}</strong></span>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- Settings Form with Tabs --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button type="submit" color="primary">
                Einstellungen speichern
            </x-filament::button>
        </div>
    </form>

    {{-- Help Section --}}
    <div class="mt-6">
        <x-filament::section>
            <x-slot name="heading">
                Hilfe & Dokumentation
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <h3 class="text-lg font-semibold">Über diese Seite</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Das Einstellungen Dashboard bietet eine zentrale Verwaltung aller Systemkonfigurationen.
                    Änderungen werden sofort gespeichert und über das Event-System synchronisiert.
                </p>

                <h4 class="text-md font-semibold mt-4">Sicherheitshinweise</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                    <li>API-Schlüssel werden verschlüsselt gespeichert (AES-256-CBC)</li>
                    <li>Alle Änderungen werden im Aktivitätsprotokoll erfasst</li>
                    <li>Nur autorisierte Benutzer können Einstellungen ändern</li>
                    <li>Super-Admins können alle Firmen verwalten</li>
                </ul>

                <h4 class="text-md font-semibold mt-4">Kategorien</h4>
                <dl class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                    <dt class="font-semibold">📞 Retell AI</dt>
                    <dd class="ml-6">KI-gestützte Telefonie und Sprachassistenten</dd>

                    <dt class="font-semibold">📅 Cal.com</dt>
                    <dd class="ml-6">Terminbuchung und Kalenderverwaltung</dd>

                    <dt class="font-semibold">✨ OpenAI</dt>
                    <dd class="ml-6">KI-Modelle und natürliche Sprachverarbeitung</dd>

                    <dt class="font-semibold">💾 Qdrant</dt>
                    <dd class="ml-6">Vektordatenbank für semantische Suche</dd>

                    <dt class="font-semibold">📆 Kalender</dt>
                    <dd class="ml-6">Kalenderansicht und Zeitzoneneinstellungen</dd>

                    <dt class="font-semibold">🛡️ Richtlinien</dt>
                    <dd class="ml-6">Storno-, Umbuchungs- und Wiederholungsrichtlinien</dd>
                </dl>
            </div>
        </x-filament::section>
    </div>

    {{-- Mobile Optimization Styles --}}
    <style>
        @media (max-width: 768px) {
            .fi-tabs-nav {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .fi-tabs-nav-item {
                flex-shrink: 0;
            }
        }
    </style>
</x-filament-panels::page>
