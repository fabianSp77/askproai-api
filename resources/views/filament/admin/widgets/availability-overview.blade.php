<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Verfügbarkeitsübersicht
        </x-slot>

        <x-slot name="headerActions">
            <x-filament::input.wrapper>
                <x-filament::input.select
                    wire:model.live="companyId"
                    wire:loading.attr="disabled"
                >
                    @foreach(\App\Models\Company::all() as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        <div wire:loading.class="opacity-50">
            @if($isLoading)
                <div class="flex justify-center py-8">
                    <x-filament::loading-indicator class="h-8 w-8" />
                </div>
            @elseif(empty($availabilityData))
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                    Keine Event-Types gefunden oder keine Verfügbarkeiten vorhanden.
                </p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($availabilityData as $data)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $data['event_type'] }}
                            </h3>
                            
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Dauer:</span>
                                    <span class="text-gray-900 dark:text-gray-100">{{ $data['duration'] }} Min.</span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                    <span class="font-medium {{ $data['available'] ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                        {{ $data['available'] ? 'Verfügbar' : 'Nicht verfügbar' }}
                                    </span>
                                </div>
                                
                                @if($data['available'])
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Nächster Termin:</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ $data['next_slot'] }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Freie Termine (7 Tage):</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ $data['total_slots'] }}</span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Verfügbare Mitarbeiter:</span>
                                        <span class="text-gray-900 dark:text-gray-100">{{ $data['staff_count'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <p>Zeigt die Verfügbarkeit für die nächsten 7 Tage. Aktualisiert sich automatisch.</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>