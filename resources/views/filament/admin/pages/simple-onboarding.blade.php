<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Erste Schritte</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="font-medium text-lg">1. Grundeinstellungen</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" />
                            <span>Unternehmensdaten vervollständigen</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" />
                            <span>Filialen und Standorte anlegen</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" />
                            <span>Mitarbeiter hinzufügen</span>
                        </li>
                    </ul>
                </div>
                
                <div class="space-y-4">
                    <h3 class="font-medium text-lg">2. Dienstleistungen</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-start">
                            <x-heroicon-o-clock class="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0" />
                            <span>Services definieren</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-clock class="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0" />
                            <span>Arbeitszeiten festlegen</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-clock class="w-5 h-5 text-yellow-500 mr-2 flex-shrink-0" />
                            <span>Mitarbeiter-Services zuordnen</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Wichtige Links</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('filament.admin.resources.companies.index') }}" 
                   class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <x-heroicon-o-building-office class="w-8 h-8 text-blue-600 mr-3" />
                    <div>
                        <div class="font-medium">Unternehmen</div>
                        <div class="text-sm text-gray-600">Verwaltung</div>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.staff.index') }}" 
                   class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <x-heroicon-o-users class="w-8 h-8 text-green-600 mr-3" />
                    <div>
                        <div class="font-medium">Mitarbeiter</div>
                        <div class="text-sm text-gray-600">Verwaltung</div>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.services.index') }}" 
                   class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <x-heroicon-o-clipboard-document-list class="w-8 h-8 text-purple-600 mr-3" />
                    <div>
                        <div class="font-medium">Services</div>
                        <div class="text-sm text-gray-600">Konfiguration</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>