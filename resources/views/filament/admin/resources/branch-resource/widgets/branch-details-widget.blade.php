<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Telefonnummern --}}
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Telefonnummern</h3>
                @if($phoneNumbers->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-500">Keine Telefonnummern zugeordnet</p>
                @else
                    <ul class="space-y-2">
                        @foreach($phoneNumbers as $phone)
                            <li class="flex items-center gap-2">
                                <x-heroicon-o-phone class="w-4 h-4 text-gray-400" />
                                <span class="text-sm">{{ $phone->number }}</span>
                                <span class="text-xs text-gray-500">({{ $phone->type }})</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Mitarbeiter --}}
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Mitarbeiter</h3>
                @if($staff->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-500">Keine Mitarbeiter zugeordnet</p>
                @else
                    <ul class="space-y-2">
                        @foreach($staff as $employee)
                            <li class="flex items-center gap-2">
                                <x-heroicon-o-user class="w-4 h-4 text-gray-400" />
                                <span class="text-sm">{{ $employee->name }}</span>
                                @if($employee->email)
                                    <span class="text-xs text-gray-500">({{ $employee->email }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Öffnungszeiten --}}
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Öffnungszeiten</h3>
                <ul class="space-y-1">
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
                    @foreach($days as $key => $dayName)
                        <li class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ $dayName }}:</span>
                            @if(isset($workingHours[$key]['closed']) && $workingHours[$key]['closed'])
                                <span class="text-gray-500">Geschlossen</span>
                            @else
                                <span>{{ $workingHours[$key]['open'] ?? '09:00' }} - {{ $workingHours[$key]['close'] ?? '18:00' }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>