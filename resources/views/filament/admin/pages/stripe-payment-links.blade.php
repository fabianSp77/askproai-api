<x-filament::page>
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-2">Stripe Payment Links verwalten</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Erstellen und verwalten Sie dauerhafte Payment Links für Ihre Kunden. 
                Diese Links können mehrfach verwendet werden und ermöglichen es Kunden, ihr Guthaben eigenständig aufzuladen.
            </p>
            
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                    <div class="flex items-center">
                        <x-heroicon-o-credit-card class="w-8 h-8 text-blue-600 dark:text-blue-400 mr-3" />
                        <div>
                            <h3 class="font-medium">Zahlungsmethoden</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Kreditkarte & SEPA</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded">
                    <div class="flex items-center">
                        <x-heroicon-o-qr-code class="w-8 h-8 text-green-600 dark:text-green-400 mr-3" />
                        <div>
                            <h3 class="font-medium">QR-Code Support</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Für physische Standorte</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded">
                    <div class="flex items-center">
                        <x-heroicon-o-refresh class="w-8 h-8 text-purple-600 dark:text-purple-400 mr-3" />
                        <div>
                            <h3 class="font-medium">Wiederverwendbar</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Dauerhaft gültig</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{ $this->table }}
</x-filament::page>