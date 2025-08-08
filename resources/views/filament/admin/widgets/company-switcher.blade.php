<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Aktuelles Unternehmen
                </h2>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $this->getCurrentCompany()?->name ?? 'Kein Unternehmen ausgewählt' }}
                </p>
            </div>
            
            @if($this->getAccessibleCompanies()->count() > 1)
                <x-filament::dropdown>
                    <x-slot name="trigger">
                        <x-filament::button
                            color="gray"
                            icon="heroicon-o-chevron-down"
                            icon-position="after"
                            size="sm"
                        >
                            Wechseln
                        </x-filament::button>
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach($this->getAccessibleCompanies() as $company)
                            <x-filament::dropdown.list.item
                                wire:click="switchCompany({{ $company->id }})"
                                :icon="$company->id === $this->getCurrentCompany()?->id ? 'heroicon-o-check' : null"
                            >
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $company->name }}</span>
                                    @if($company->isResellerClient())
                                        <span class="text-xs text-gray-500">
                                            Kunde von {{ $company->parentCompany->name }}
                                        </span>
                                    @elseif($company->isReseller())
                                        <span class="text-xs text-primary-600">
                                            Vermittler ({{ $company->childCompanies->count() }} Kunden)
                                        </span>
                                    @endif
                                </div>
                            </x-filament::dropdown.list.item>
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            @endif
        </div>
        
        @if($this->getCurrentCompany() && $this->getCurrentCompany()->isResellerClient())
            <div class="mt-4 p-3 bg-primary-50 dark:bg-primary-900/10 rounded-lg">
                <p class="text-sm text-primary-600 dark:text-primary-400">
                    <span class="font-medium">Vermittler:</span> {{ $this->getCurrentCompany()->parentCompany->name }}
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>