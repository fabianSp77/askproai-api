<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Progress Steps -->
        <div class="flex items-center justify-center space-x-4">
            @for ($i = 1; $i <= 4; $i++)
                <div class="flex items-center">
                    <div @class([
                        'w-10 h-10 rounded-full flex items-center justify-center font-semibold',
                        'bg-primary-600 text-white' => $i <= $currentStep,
                        'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $i > $currentStep,
                    ])>
                        {{ $i }}
                    </div>
                    @if ($i < 4)
                        <div @class([
                            'w-20 h-0.5 mx-2',
                            'bg-primary-600' => $i < $currentStep,
                            'bg-gray-200 dark:bg-gray-700' => $i >= $currentStep,
                        ])></div>
                    @endif
                </div>
            @endfor
        </div>
        
        <!-- Step Title -->
        <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-gray-100">
            {{ $this->stepTitle }}
        </h2>
        
        <!-- Step Content -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            @if ($currentStep === 1)
                <!-- Step 1: Company & Branch Selection -->
                <form wire:submit.prevent="">
                    {{ $this->form }}
                </form>
                
            @elseif ($currentStep === 2)
                <!-- Step 2: Event-Types Preview with Search & Filter -->
                <div class="space-y-4">
                    <!-- Header Stats -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-blue-600 dark:text-blue-400">
                                    Gefundene Event-Types: <strong>{{ count($eventTypesPreview) }}</strong>
                                </p>
                                <p class="text-xs text-blue-500 dark:text-blue-300 mt-1">
                                    Ausgewählt: <strong>{{ count(array_filter($importSelections)) }}</strong>
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <x-filament::button 
                                    wire:click="selectAll"
                                    size="sm"
                                    color="gray">
                                    Alle auswählen
                                </x-filament::button>
                                <x-filament::button 
                                    wire:click="deselectAll"
                                    size="sm"
                                    color="gray">
                                    Keine auswählen
                                </x-filament::button>
                                <x-filament::button 
                                    wire:click="selectSmart"
                                    size="sm"
                                    color="primary">
                                    Intelligent auswählen
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search & Filter -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">
                                Suche
                            </label>
                            <input type="text"
                                   wire:model.live.debounce.300ms="searchQuery"
                                   placeholder="Nach Event-Type Namen suchen..."
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">
                                Team-Filter
                            </label>
                            <select wire:model.live="filterTeam"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="all">Alle Teams</option>
                                @foreach($this->getUniqueTeams() as $teamId => $teamName)
                                    <option value="{{ $teamId }}">{{ $teamName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <!-- Event Types Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-10">
                                        <input type="checkbox" 
                                               wire:click="toggleAllSelections"
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Event-Type Details
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Import-Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Team / Einstellungen
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($this->getFilteredEventTypes() as $index => $preview)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" 
                                                   wire:model.live="importSelections.{{ $index }}"
                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $preview['original_name'] }}
                                                </div>
                                                <div class="flex items-center space-x-4 mt-1">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        <x-heroicon-o-clock class="inline w-3 h-3" />
                                                        {{ $preview['original']['length'] ?? 30 }} Min
                                                    </span>
                                                    @if($preview['original']['price']['amount'] ?? null)
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            <x-heroicon-o-currency-euro class="inline w-3 h-3" />
                                                            {{ number_format($preview['original']['price']['amount'] / 100, 2) }} €
                                                        </span>
                                                    @endif
                                                    @if($preview['original']['requiresConfirmation'] ?? false)
                                                        <span class="text-xs text-amber-600 dark:text-amber-400">
                                                            <x-heroicon-o-shield-check class="inline w-3 h-3" />
                                                            Bestätigung erforderlich
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                                    {{ $preview['suggested_name'] }}
                                                </div>
                                                @if($preview['extracted_service'] ?? null)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        Service: {{ $preview['extracted_service'] }}
                                                    </div>
                                                @endif
                                                @if($preview['name_options'] ?? null)
                                                    <details class="mt-1">
                                                        <summary class="text-xs text-blue-600 dark:text-blue-400 cursor-pointer">
                                                            Alternative Namen anzeigen
                                                        </summary>
                                                        <div class="mt-1 space-y-1">
                                                            @foreach($preview['name_options'] as $format => $name)
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                    {{ ucfirst($format) }}: {{ $name }}
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="space-y-1">
                                                @if($preview['original']['team'] ?? null)
                                                    <div class="text-xs">
                                                        <span class="text-gray-500 dark:text-gray-400">Team:</span>
                                                        <span class="font-medium">{{ $preview['original']['team']['name'] ?? 'N/A' }}</span>
                                                    </div>
                                                @endif
                                                @if($preview['original']['schedulingType'] ?? null)
                                                    <div class="text-xs">
                                                        <span class="text-gray-500 dark:text-gray-400">Typ:</span>
                                                        <span class="font-medium">
                                                            {{ $preview['original']['schedulingType'] === 'COLLECTIVE' ? 'Team-Event' : 'Einzel-Event' }}
                                                        </span>
                                                    </div>
                                                @endif
                                                @if($preview['original']['minimumBookingNotice'] ?? null)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Min. Vorlauf: {{ $preview['original']['minimumBookingNotice'] }} Min
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="space-y-1">
                                                @if(!($preview['original']['active'] ?? true))
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                        Inaktiv
                                                    </span>
                                                @elseif($preview['matches_branch'] ?? false)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                        Passt zur Filiale
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                        Bereit
                                                    </span>
                                                @endif
                                                
                                                @if($preview['warning'] ?? null)
                                                    <div class="text-xs text-amber-600 dark:text-amber-400">
                                                        <x-heroicon-o-exclamation-triangle class="inline w-3 h-3" />
                                                        {{ $preview['warning'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if(count($this->getFilteredEventTypes()) === 0)
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-magnifying-glass class="w-12 h-12 mx-auto mb-4 text-gray-400" />
                            <p>Keine Event-Types gefunden.</p>
                            <p class="text-sm mt-1">Versuchen Sie eine andere Suche oder ändern Sie den Filter.</p>
                        </div>
                    @endif
                </div>
                
            @elseif ($currentStep === 3)
                <!-- Step 3: Mapping Correction -->
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Überprüfen und korrigieren Sie bei Bedarf die Service-Namen. Diese werden für die Benennung in AskProAI verwendet.
                    </p>
                    
                    <div class="space-y-3">
                        @foreach($mappings as $index => $mapping)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Original</label>
                                        <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ $mapping['original_name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $mapping['duration'] ?? 30 }} Min
                                            @if($mapping['price'] ?? null)
                                                · {{ number_format($mapping['price'] / 100, 2) }} €
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Service Name</label>
                                        <input type="text" 
                                               wire:model="mappings.{{ $index }}.service_name"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Extrahiert: {{ $mapping['extracted_service'] ?? 'N/A' }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Endergebnis</label>
                                        <p class="text-sm font-medium text-primary-600 dark:text-primary-400 mt-1">
                                            {{ $mapping['final_name'] ?? $mapping['suggested_name'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
            @elseif ($currentStep === 4)
                <!-- Step 4: Import Summary -->
                <div class="space-y-4">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <h3 class="font-semibold text-green-900 dark:text-green-100 mb-2">Import erfolgreich!</h3>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            {{ count($importSummary) }} Event-Types wurden erfolgreich importiert.
                        </p>
                    </div>
                    
                    <div class="space-y-2">
                        @foreach($importSummary as $summary)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $summary['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Cal.com ID: {{ $summary['calcom_id'] }}
                                    </p>
                                </div>
                                <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Navigation Buttons -->
        <div class="flex justify-between">
            <div>
                @if($currentStep > 1)
                    <x-filament::button 
                        wire:click="previousStep"
                        color="gray">
                        Zurück
                    </x-filament::button>
                @endif
            </div>
            
            <div>
                @if($currentStep < 4)
                    <x-filament::button 
                        wire:click="nextStep"
                        :disabled="!$this->canProceed()">
                        Weiter
                    </x-filament::button>
                @else
                    <x-filament::button 
                        wire:click="finish"
                        color="success">
                        Fertig
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>