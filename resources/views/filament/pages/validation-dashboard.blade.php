<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-success-600">23</div>
                    <div class="text-sm text-gray-500">Aktive Services</div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-warning-600">3</div>
                    <div class="text-sm text-gray-500">Warnungen</div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-danger-600">0</div>
                    <div class="text-sm text-gray-500">Fehler</div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">99.8%</div>
                    <div class="text-sm text-gray-500">System Health</div>
                </div>
            </x-filament::card>
        </div>

        <!-- Critical Issues -->
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">🚨 Kritische Probleme</h2>
            <div class="space-y-2">
                <div class="p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                    <div class="font-medium text-danger-700 dark:text-danger-400">
                        Service "Haarschnitt Premium" - Filiale Hamburg
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Preismatrix-Konflikt: Mitarbeiterpreis überschreitet Filialmaximum
                    </div>
                </div>
            </div>
        </x-filament::card>

        <!-- KI-Assistant -->
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">🤖 KI-Assistent</h2>
            <div class="space-y-4">
                <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                    <div class="font-medium">💡 Optimierungsvorschlag</div>
                    <div class="text-sm mt-2">
                        Die Services in der Filiale München haben eine 23% höhere Fehlerrate. 
                        Empfehle Synchronisation mit Hamburg-Konfiguration.
                    </div>
                </div>
                
                <div class="p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                    <div class="font-medium">✅ Muster erkannt</div>
                    <div class="text-sm mt-2">
                        Neue Services werden oft ohne Mitarbeiterzuweisung erstellt. 
                        Automatische Zuweisung aktivieren?
                    </div>
                </div>
            </div>
        </x-filament::card>

        <!-- Live Validation Stream -->
        <x-filament::card>
            <h2 class="text-lg font-bold mb-4">🔄 Live Validation Stream</h2>
            <div class="text-center py-8">
                <div class="text-gray-500">Echtzeit-Überwachung aktiv</div>
                <div class="mt-2">
                    <span class="inline-flex h-3 w-3 animate-pulse rounded-full bg-success-400"></span>
                </div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
