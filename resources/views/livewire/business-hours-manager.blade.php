<div>
    <div class="space-y-4">
        <!-- Template Auswahl -->
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <label class="block text-sm font-medium mb-2">Vorlage verwenden</label>
            <div class="flex gap-2">
                <select wire:model="selectedTemplate" class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">-- Vorlage wählen --</option>
                    @foreach($templates as $template)
                        @if(is_array($template))
                            <option value="{{ $template['id'] }}">{{ $template['name'] }}</option>
                        @else
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endif
                    @endforeach
                </select>
                <button type="button" wire:click="applyTemplate" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    Anwenden
                </button>
            </div>
        </div>

        <!-- Geschäftszeiten Tabelle -->
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left">Wochentag</th>
                        <th class="px-4 py-2 text-left">Geschlossen</th>
                        <th class="px-4 py-2 text-left">Öffnung</th>
                        <th class="px-4 py-2 text-left">Schließung</th>
                        <th class="px-4 py-2 text-left">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $days = [
                            'monday' => 'Montag',
                            'tuesday' => 'Dienstag',
                            'wednesday' => 'Mittwoch',
                            'thursday' => 'Donnerstag',
                            'friday' => 'Freitag',
                            'saturday' => 'Samstag',
                            'sunday' => 'Sonntag'
                        ];
                    @endphp
                    
                    @foreach($days as $key => $label)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $label }}</td>
                            <td class="px-4 py-2">
                                <input type="checkbox" 
                                       wire:model="businessHours.{{ $key }}.closed"
                                       wire:change="updateHours"
                                       class="rounded">
                            </td>
                            <td class="px-4 py-2">
                                <input type="time" 
                                       wire:model="businessHours.{{ $key }}.open"
                                       wire:change="updateHours"
                                       @if($businessHours[$key]['closed'] ?? false) disabled @endif
                                       class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 disabled:opacity-50">
                            </td>
                            <td class="px-4 py-2">
                                <input type="time" 
                                       wire:model="businessHours.{{ $key }}.close"
                                       wire:change="updateHours"
                                       @if($businessHours[$key]['closed'] ?? false) disabled @endif
                                       class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 disabled:opacity-50">
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex gap-1">
                                    @if($key !== 'monday')
                                        <button type="button" 
                                                wire:click="copyFromPreviousDay('{{ $key }}')"
                                                title="Von vorherigem Tag kopieren"
                                                class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded">
                                            ↑
                                        </button>
                                    @endif
                                    
                                    @if(in_array($key, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']))
                                        <button type="button" 
                                                wire:click="copyToAllWeekdays('{{ $key }}')"
                                                title="Auf alle Wochentage kopieren"
                                                class="text-xs px-2 py-1 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 rounded">
                                            Mo-Fr
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
