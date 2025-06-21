<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @php
                $stats = $this->getStats();
            @endphp
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-700 dark:text-gray-300">
                        {{ number_format($stats['total']) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Kunden gesamt
                    </div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($stats['with_email']) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Mit E-Mail
                    </div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($stats['portal_enabled']) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Portal aktiviert
                    </div>
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ number_format($stats['active_users']) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Aktive Nutzer (30 Tage)
                    </div>
                </div>
            </x-filament::card>
        </div>
        
        <!-- Info Box -->
        <x-filament::card>
            <div class="flex items-start space-x-3">
                <x-heroicon-o-information-circle class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" />
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Kundenportal-Verwaltung
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Hier können Sie den Portal-Zugang für Ihre Kunden verwalten. Kunden mit aktiviertem Portal können:
                    </p>
                    <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 mt-2 space-y-1">
                        <li>Ihre Termine einsehen und verwalten</li>
                        <li>Rechnungen herunterladen</li>
                        <li>Ihr Profil aktualisieren</li>
                    </ul>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                        Portal-URL: <code class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ config('app.url') }}/portal</code>
                    </p>
                </div>
            </div>
        </x-filament::card>
        
        <!-- Table -->
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>