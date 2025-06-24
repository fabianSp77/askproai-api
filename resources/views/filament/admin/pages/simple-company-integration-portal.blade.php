<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Company Selection Form --}}
        {{ $this->companyForm }}
        
        {{-- Show integration forms only when company is selected --}}
        @if($selectedCompany)
            {{-- Cal.com Integration Form --}}
            {{ $this->calcomForm }}
            
            {{-- Retell.ai Integration Form --}}
            {{ $this->retellForm }}
            
            {{-- Quick Status Overview --}}
            <x-filament::section>
                <x-slot name="heading">
                    Integrationsstatus
                </x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Cal.com Status --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-calendar class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                            <h3 class="font-medium">Cal.com</h3>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            @if(!empty($calcomData['calcom_api_key']))
                                <span class="text-green-600 dark:text-green-400">Konfiguriert</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">Nicht konfiguriert</span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Retell.ai Status --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-phone class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                            <h3 class="font-medium">Retell.ai</h3>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            @if(!empty($retellData['retell_api_key']))
                                <span class="text-green-600 dark:text-green-400">Konfiguriert</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">Nicht konfiguriert</span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Phone Numbers Status --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-device-phone-mobile class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                            <h3 class="font-medium">Telefonnummern</h3>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $selectedCompany->phoneNumbers()->count() }} konfiguriert
                        </div>
                    </div>
                </div>
            </x-filament::section>
            
            {{-- Branch and Phone Number Configuration --}}
            <x-filament::section collapsible>
                <x-slot name="heading">
                    Filialen & Telefonnummern
                </x-slot>
                
                <div class="space-y-4">
                    @foreach($selectedCompany->branches as $branch)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h4 class="font-medium mb-2">{{ $branch->name }}</h4>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p>{{ $branch->address }}</p>
                                @if($branch->phoneNumbers->count() > 0)
                                    <p class="mt-2">Telefonnummern:</p>
                                    <ul class="list-disc list-inside ml-2">
                                        @foreach($branch->phoneNumbers as $phone)
                                            <li>
                                                {{ $phone->number }}
                                                @if($phone->is_primary)
                                                    <span class="text-xs bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 px-2 py-0.5 rounded-full ml-2">Primär</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-yellow-600 dark:text-yellow-400 mt-2">Keine Telefonnummern konfiguriert</p>
                                @endif
                            </div>
                            <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                Filiale ID: {{ $branch->id }}
                            </div>
                        </div>
                    @endforeach
                    
                    @if($selectedCompany->branches->count() === 0)
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-building-office-2 class="w-12 h-12 mx-auto mb-3" />
                            <p>Keine Filialen vorhanden</p>
                            <p class="text-sm mt-2">Bitte legen Sie zuerst eine Filiale über das Admin-Panel an.</p>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>