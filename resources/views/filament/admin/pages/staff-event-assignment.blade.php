<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Company Selector -->
        <div class="max-w-xl">
            {{ $this->form }}
        </div>
        
        @if($company_id && count($staff) > 0 && count($eventTypes) > 0)
            <!-- Action Buttons -->
            <div class="flex gap-4 items-center">
                <x-filament::button
                    wire:click="selectAll"
                    color="gray"
                    size="sm"
                >
                    Alle auswählen
                </x-filament::button>
                
                <x-filament::button
                    wire:click="deselectAll"
                    color="gray"
                    size="sm"
                >
                    Alle abwählen
                </x-filament::button>
                
                <div class="ml-auto">
                    <x-filament::button
                        wire:click="saveAssignments"
                        icon="heroicon-o-check"
                    >
                        Zuordnungen speichern
                    </x-filament::button>
                </div>
            </div>
            
            <!-- Matrix Table -->
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-800">
                                Mitarbeiter
                            </th>
                            @foreach($eventTypes as $eventType)
                                <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <span class="font-semibold">{{ $eventType['name'] }}</span>
                                        <span class="text-xs text-gray-400">{{ $eventType['duration'] }} Min.</span>
                                        @if($eventType['price'])
                                            <span class="text-xs text-gray-400">{{ number_format($eventType['price'], 2) }} €</span>
                                        @endif
                                        <x-filament::link
                                            wire:click="selectAllForEventType({{ $eventType['id'] }})"
                                            class="text-xs mt-1"
                                        >
                                            Alle
                                        </x-filament::link>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($staff as $staffMember)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-900">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $staffMember['name'] }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $staffMember['branch'] }}
                                        </span>
                                        <x-filament::link
                                            wire:click="selectAllForStaff('{{ $staffMember['id'] }}')"
                                            class="text-xs mt-1"
                                        >
                                            Alle auswählen
                                        </x-filament::link>
                                    </div>
                                </td>
                                @foreach($eventTypes as $eventType)
                                    @php
                                        $key = $staffMember['id'] . '::' . $eventType['id'];
                                        $isAssigned = $assignments[$key]['assigned'] ?? false;
                                    @endphp
                                    <td class="px-2 py-3 text-center">
                                        <label class="inline-flex items-center justify-center">
                                            <input
                                                type="checkbox"
                                                wire:model="assignments.{{ $key }}.assigned"
                                                wire:click="toggleAssignment('{{ $staffMember['id'] }}', {{ $eventType['id'] }})"
                                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
                                                title="Key: {{ $key }}"
                                            >
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Legend -->
            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                <p>✓ = Mitarbeiter ist diesem Event-Type zugeordnet</p>
                <p>Klicken Sie auf die Checkboxen um Zuordnungen zu ändern.</p>
            </div>
            
        @elseif($company_id)
            <x-filament::section>
                <x-slot name="heading">
                    Keine Daten gefunden
                </x-slot>
                
                <p class="text-gray-600 dark:text-gray-400">
                    Es wurden keine aktiven Mitarbeiter oder Event-Types für dieses Unternehmen gefunden.
                    Bitte stellen Sie sicher, dass:
                </p>
                <ul class="list-disc list-inside mt-2 text-gray-600 dark:text-gray-400">
                    <li>Mitarbeiter angelegt und als aktiv markiert sind</li>
                    <li>Event-Types von Cal.com synchronisiert wurden</li>
                    <li>Event-Types als aktiv markiert sind</li>
                </ul>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>