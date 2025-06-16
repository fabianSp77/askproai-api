<x-filament-panels::page>
    <div class="space-y-6" wire:loading.class="opacity-50">
        <!-- Debug: Assignments Count -->
        @if(config('app.debug'))
        <div class="text-xs text-gray-500">
            Assignments loaded: {{ count($assignments) }} | 
            Staff: {{ count($staff) }} | 
            EventTypes: {{ count($eventTypes) }}
        </div>
        @endif
        
        <!-- Company Selector mit modernem Design -->
        <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-6 shadow-lg">
            <div class="max-w-xl">
                {{ $this->form }}
            </div>
        </div>
        
        @if($company_id && count($staff) > 0 && count($eventTypes) > 0)
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Mitarbeiter</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ count($staff) }}</p>
                        </div>
                        <x-heroicon-o-user-group class="w-8 h-8 text-primary-500" />
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Event-Types</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ count($eventTypes) }}</p>
                        </div>
                        <x-heroicon-o-calendar class="w-8 h-8 text-primary-500" />
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Zuordnungen</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ collect($assignments)->filter(fn($a) => $a['assigned'] ?? false)->count() }}
                            </p>
                        </div>
                        <x-heroicon-o-link class="w-8 h-8 text-primary-500" />
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Abdeckung</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ round((collect($assignments)->filter(fn($a) => $a['assigned'] ?? false)->count() / (count($staff) * count($eventTypes))) * 100) }}%
                            </p>
                        </div>
                        <x-heroicon-o-chart-pie class="w-8 h-8 text-primary-500" />
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons mit besserem Design -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex gap-2">
                        <x-filament::button
                            wire:click="selectAll"
                            color="gray"
                            size="sm"
                            icon="heroicon-o-check-circle"
                        >
                            Alle auswählen
                        </x-filament::button>
                        
                        <x-filament::button
                            wire:click="deselectAll"
                            color="gray"
                            size="sm"
                            icon="heroicon-o-x-circle"
                        >
                            Alle abwählen
                        </x-filament::button>
                        
                        <x-filament::dropdown>
                            <x-slot name="trigger">
                                <x-filament::button
                                    color="gray"
                                    size="sm"
                                    icon="heroicon-o-sparkles"
                                >
                                    Smart Actions
                                </x-filament::button>
                            </x-slot>
                            
                            <x-filament::dropdown.list>
                                <x-filament::dropdown.list.item
                                    wire:click="applyTemplate('basic')"
                                    icon="heroicon-o-document-duplicate"
                                >
                                    Basis-Vorlage anwenden
                                </x-filament::dropdown.list.item>
                                
                                <x-filament::dropdown.list.item
                                    wire:click="distributeEvenly"
                                    icon="heroicon-o-scale"
                                >
                                    Gleichmäßig verteilen
                                </x-filament::dropdown.list.item>
                                
                                <x-filament::dropdown.list.item
                                    wire:click="applyIntelligentMatching"
                                    icon="heroicon-o-sparkles"
                                >
                                    Intelligente Zuordnung
                                </x-filament::dropdown.list.item>
                                
                                <x-filament::dropdown.list.item
                                    wire:click="$set('showSkillMatrix', true)"
                                    icon="heroicon-o-academic-cap"
                                >
                                    Nach Skills zuordnen
                                </x-filament::dropdown.list.item>
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </div>
                    
                    <div class="flex gap-2">
                        <x-filament::button
                            wire:click="exportAssignments"
                            color="gray"
                            size="sm"
                            icon="heroicon-o-arrow-down-tray"
                        >
                            Export
                        </x-filament::button>
                        
                        <x-filament::button
                            wire:click="saveAssignments"
                            icon="heroicon-o-check"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Zuordnungen speichern</span>
                            <span wire:loading>Speichere...</span>
                        </x-filament::button>
                    </div>
                </div>
            </div>
            
            <!-- Toggle zwischen Ansichten -->
            <div class="flex gap-2 justify-center">
                <x-filament::button
                    wire:click="$set('viewMode', 'matrix')"
                    :color="$viewMode === 'matrix' ? 'primary' : 'gray'"
                    size="sm"
                    icon="heroicon-o-table-cells"
                >
                    Matrix
                </x-filament::button>
                
                <x-filament::button
                    wire:click="$set('viewMode', 'cards')"
                    :color="$viewMode === 'cards' ? 'primary' : 'gray'"
                    size="sm"
                    icon="heroicon-o-squares-2x2"
                >
                    Karten
                </x-filament::button>
                
                <x-filament::button
                    wire:click="$set('viewMode', 'kanban')"
                    :color="$viewMode === 'kanban' ? 'primary' : 'gray'"
                    size="sm"
                    icon="heroicon-o-view-columns"
                >
                    Kanban
                </x-filament::button>
            </div>
            
            @if($viewMode === 'matrix')
                <!-- Verbesserte Matrix Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 z-10">
                                        Mitarbeiter
                                    </th>
                                    @foreach($eventTypes as $eventType)
                                        <th class="px-3 py-4 text-center text-xs font-medium text-gray-600 dark:text-gray-400 min-w-[120px]">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                                    {{ Str::limit($eventType['name'], 20) }}
                                                </span>
                                                <div class="flex items-center gap-2 text-xs">
                                                    <span class="inline-flex items-center gap-1">
                                                        <x-heroicon-o-clock class="w-3 h-3" />
                                                        {{ $eventType['duration'] }}m
                                                    </span>
                                                    @if($eventType['price'])
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-heroicon-o-currency-euro class="w-3 h-3" />
                                                            {{ number_format($eventType['price'], 2) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <button
                                                    wire:click="selectAllForEventType({{ $eventType['id'] }})"
                                                    class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 text-xs font-medium"
                                                >
                                                    Alle
                                                </button>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($staff as $staffMember)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800 z-10">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-semibold">
                                                    {{ substr($staffMember['name'], 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $staffMember['name'] }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $staffMember['branch'] }}
                                                    </div>
                                                    <button
                                                        wire:click="selectAllForStaff('{{ $staffMember['id'] }}')"
                                                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 text-xs font-medium"
                                                    >
                                                        Alle auswählen
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        @foreach($eventTypes as $eventType)
                                            @php
                                                $key = $staffMember['id'] . '::' . $eventType['id'];
                                                $isAssigned = $assignments[$key]['assigned'] ?? false;
                                                $score = $assignments[$key]['score'] ?? 0;
                                                $scoreColor = $score >= 70 ? 'green' : ($score >= 40 ? 'yellow' : 'red');
                                            @endphp
                                            <td class="px-3 py-4 text-center">
                                                <div class="relative group">
                                                    <div class="relative inline-flex items-center justify-center">
                                                        <button
                                                            type="button"
                                                            wire:click="toggleAssignment('{{ $staffMember['id'] }}', {{ $eventType['id'] }})"
                                                            class="w-12 h-12 rounded-lg border-2 transition-all duration-200 cursor-pointer
                                                                @if($isAssigned) 
                                                                    bg-primary-100 border-primary-500 dark:bg-primary-900/50
                                                                @else 
                                                                    border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-500
                                                                @endif
                                                                group-hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                                        >
                                                            @if($isAssigned)
                                                                <x-heroicon-o-check class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                                            @endif
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Score Indicator -->
                                                    <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                                                        @if($scoreColor === 'green') bg-green-500 text-white
                                                        @elseif($scoreColor === 'yellow') bg-yellow-500 text-gray-900
                                                        @else bg-red-500 text-white
                                                        @endif">
                                                        {{ round($score) }}
                                                    </div>
                                                    
                                                    <!-- Tooltip mit Details -->
                                                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-20 whitespace-nowrap">
                                                        <div class="space-y-1">
                                                            <div>Score: {{ $score }}%</div>
                                                            <div>Termine: {{ $assignments[$key]['performance']['appointments'] ?? 0 }}</div>
                                                            <div>Erfolg: {{ $assignments[$key]['performance']['success_rate'] ?? 0 }}%</div>
                                                        </div>
                                                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                            <div class="w-0 h-0 border-l-[5px] border-l-transparent border-t-[5px] border-t-gray-900 border-r-[5px] border-r-transparent"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif($viewMode === 'cards')
                <!-- Karten-Ansicht -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($staff as $staffMember)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-lg">
                                    {{ substr($staffMember['name'], 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $staffMember['name'] }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $staffMember['branch'] }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                @foreach($eventTypes as $eventType)
                                    @php
                                        $key = $staffMember['id'] . '::' . $eventType['id'];
                                        $isAssigned = $assignments[$key]['assigned'] ?? false;
                                    @endphp
                                    <div 
                                        wire:click="toggleAssignment('{{ $staffMember['id'] }}', {{ $eventType['id'] }})"
                                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
                                    >
                                        <div class="flex items-center justify-center w-5 h-5 rounded border-2 transition-all
                                            @if($isAssigned) 
                                                bg-primary-600 border-primary-600
                                            @else 
                                                bg-white border-gray-300 dark:bg-gray-800 dark:border-gray-600
                                            @endif">
                                            @if($isAssigned)
                                                <x-heroicon-m-check class="w-3 h-3 text-white" />
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $eventType['name'] }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $eventType['duration'] }} Min. 
                                                @if($eventType['price'])
                                                    • {{ number_format($eventType['price'], 2) }} €
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($viewMode === 'kanban')
                <!-- Kanban-Ansicht -->
                <div class="flex gap-4 overflow-x-auto pb-4">
                    @foreach($eventTypes as $eventType)
                        <div class="flex-shrink-0 w-80">
                            <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-4">
                                <div class="mb-4">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $eventType['name'] }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $eventType['duration'] }} Min. 
                                        @if($eventType['price'])
                                            • {{ number_format($eventType['price'], 2) }} €
                                        @endif
                                    </p>
                                </div>
                                
                                <div class="space-y-2">
                                    @foreach($staff as $staffMember)
                                        @php
                                            $key = $staffMember['id'] . '::' . $eventType['id'];
                                            $isAssigned = $assignments[$key]['assigned'] ?? false;
                                        @endphp
                                        @if($isAssigned)
                                            <div class="bg-white dark:bg-gray-700 rounded-lg p-3 shadow-sm">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-sm font-semibold">
                                                            {{ substr($staffMember['name'], 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {{ $staffMember['name'] }}
                                                            </p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $staffMember['branch'] }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <button
                                                        wire:click="toggleAssignment('{{ $staffMember['id'] }}', {{ $eventType['id'] }})"
                                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                                    >
                                                        <x-heroicon-o-x-mark class="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                
                                <!-- Nicht zugeordnete Mitarbeiter -->
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Verfügbare Mitarbeiter:</p>
                                    <div class="space-y-1">
                                        @foreach($staff as $staffMember)
                                            @php
                                                $key = $staffMember['id'] . '::' . $eventType['id'];
                                                $isAssigned = $assignments[$key]['assigned'] ?? false;
                                            @endphp
                                            @if(!$isAssigned)
                                                <button
                                                    wire:click="toggleAssignment('{{ $staffMember['id'] }}', {{ $eventType['id'] }})"
                                                    class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                                >
                                                    + {{ $staffMember['name'] }}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            
            <!-- Legende mit besserem Design -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-4 border border-blue-200 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <p class="font-medium mb-1">Hinweise zur Bedienung:</p>
                        <ul class="space-y-1 text-gray-600 dark:text-gray-400">
                            <li>• Klicken Sie auf die Checkboxen um Zuordnungen zu ändern</li>
                            <li>• Nutzen Sie "Alle" Links für schnelle Mehrfachauswahl</li>
                            <li>• Änderungen werden erst nach "Zuordnungen speichern" übernommen</li>
                            <li>• Die Prozentanzeige zeigt die Gesamtabdeckung aller möglichen Kombinationen</li>
                        </ul>
                    </div>
                </div>
            </div>
            
        @elseif($company_id)
            <!-- Keine Daten Zustand mit besserem Design -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                <div class="text-center">
                    <x-heroicon-o-inbox class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Keine Daten gefunden
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                        Es wurden keine aktiven Mitarbeiter oder Event-Types für dieses Unternehmen gefunden.
                    </p>
                    
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 max-w-lg mx-auto">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Bitte stellen Sie sicher, dass:
                        </p>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-gray-400" />
                                Mitarbeiter angelegt und als aktiv markiert sind
                            </li>
                            <li class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-gray-400" />
                                Event-Types von Cal.com synchronisiert wurden
                            </li>
                            <li class="flex items-center gap-2">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-gray-400" />
                                Event-Types als aktiv markiert sind
                            </li>
                        </ul>
                    </div>
                    
                    <div class="mt-6 flex gap-3 justify-center">
                        <x-filament::button
                            href="{{ route('filament.admin.resources.staff.index') }}"
                            tag="a"
                            color="gray"
                            icon="heroicon-o-user-group"
                        >
                            Mitarbeiter verwalten
                        </x-filament::button>
                        
                        <x-filament::button
                            href="{{ route('filament.admin.pages.event-type-import-wizard') }}"
                            tag="a"
                            icon="heroicon-o-arrow-down-tray"
                        >
                            Event-Types importieren
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @else
            <!-- Willkommens-Zustand -->
            <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8">
                <div class="text-center">
                    <x-heroicon-o-user-group class="w-20 h-20 text-primary-500 mx-auto mb-4" />
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        Mitarbeiter-Zuordnung
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                        Wählen Sie ein Unternehmen aus, um die Zuordnung von Mitarbeitern zu Event-Types zu verwalten.
                    </p>
                </div>
            </div>
        @endif
    </div>
    
    @push('scripts')
    <script>
        // Smooth scroll für horizontale Kanban-Ansicht
        const kanbanContainer = document.querySelector('.overflow-x-auto');
        if (kanbanContainer) {
            let isDown = false;
            let startX;
            let scrollLeft;
            
            kanbanContainer.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - kanbanContainer.offsetLeft;
                scrollLeft = kanbanContainer.scrollLeft;
            });
            
            kanbanContainer.addEventListener('mouseleave', () => {
                isDown = false;
            });
            
            kanbanContainer.addEventListener('mouseup', () => {
                isDown = false;
            });
            
            kanbanContainer.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - kanbanContainer.offsetLeft;
                const walk = (x - startX) * 2;
                kanbanContainer.scrollLeft = scrollLeft - walk;
            });
        }
    </script>
    @endpush
</x-filament-panels::page>