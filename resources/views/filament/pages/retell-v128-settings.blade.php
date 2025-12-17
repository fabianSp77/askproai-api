<x-filament-panels::page>
    {{-- Header Info --}}
    <div class="mb-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-primary-500" />
                    Retell AI V128 Optimierungen
                </div>
            </x-slot>

            <x-slot name="description">
                Konfigurieren Sie das Verhalten des KI-Telefonassistenten für bessere Kundenerlebnisse.
            </x-slot>

            <div class="flex items-center gap-4 text-sm">
                @if($company)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                        <x-heroicon-o-building-office class="w-4 h-4" />
                        {{ $company->name }}
                    </span>
                @endif
                <span class="text-gray-500 dark:text-gray-400">
                    Version V128 | Stand: 14.12.2025
                </span>
            </div>
        </x-filament::section>
    </div>

    {{-- Settings Form --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-between items-center">
            <x-filament::button
                type="button"
                color="gray"
                wire:click="resetToDefaults"
                wire:confirm="Wirklich alle Einstellungen auf Standard zurücksetzen?"
            >
                <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                Auf Standard zurücksetzen
            </x-filament::button>

            <x-filament::button type="submit" color="primary">
                <x-heroicon-o-check class="w-4 h-4 mr-2" />
                Einstellungen speichern
            </x-filament::button>
        </div>
    </form>

    {{-- Quick Reference --}}
    <div class="mt-6">
        <x-filament::section collapsed>
            <x-slot name="heading">
                Schnellreferenz
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                    <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-2">
                        Zeit-Shift Beispiel
                    </h4>
                    <p class="text-blue-600 dark:text-blue-400">
                        <strong>Kunde:</strong> "Dienstag Vormittag"<br>
                        <strong>Agent:</strong> "Vormittags ist leider ausgebucht. Soll ich am Mittwoch Vormittag schauen, oder passt heute Abend? Ich habe noch 20:45 oder 21:40 frei."
                    </p>
                </div>

                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                    <h4 class="font-semibold text-green-700 dark:text-green-300 mb-2">
                        Name-Skip Beispiel
                    </h4>
                    <p class="text-green-600 dark:text-green-400">
                        <strong>Bekannter Kunde ruft an</strong><br>
                        <strong>Agent:</strong> "Hallo Hans! Ich buche den Termin direkt für Hans Schuster."<br>
                        <em>(Keine Namensabfrage mehr!)</em>
                    </p>
                </div>

                <div class="p-4 rounded-lg bg-purple-50 dark:bg-purple-900/20">
                    <h4 class="font-semibold text-purple-700 dark:text-purple-300 mb-2">
                        Vollständige Bestätigung
                    </h4>
                    <p class="text-purple-600 dark:text-purple-400">
                        "Perfekt! Ihr Termin für <strong>Herrenhaarschnitt (45 Min)</strong> am <strong>Dienstag, 15. Dezember um 20:45 Uhr</strong> ist für <strong>Hans Schuster</strong> gebucht."
                    </p>
                </div>

                <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                    <h4 class="font-semibold text-amber-700 dark:text-amber-300 mb-2">
                        Stille-Handling
                    </h4>
                    <p class="text-amber-600 dark:text-amber-400">
                        <strong>20s Stille:</strong> "Sind Sie noch da?"<br>
                        <strong>Wieder 20s:</strong> "Rufen Sie gerne wieder an. Auf Wiederhören!"<br>
                        <em>(Verhindert Endlos-Schleifen)</em>
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
