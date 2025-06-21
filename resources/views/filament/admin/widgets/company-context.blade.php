<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between space-x-4">
            {{-- Company Context --}}
            @if($this->getCompany())
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-building-office class="w-5 h-5 text-gray-400" />
                    <span class="text-sm font-medium">{{ $this->getCompany()->name }}</span>
                    
                    @if($this->canSwitchCompany())
                        <x-filament::dropdown>
                            <x-slot name="trigger">
                                <x-filament::icon-button 
                                    icon="heroicon-o-chevron-down"
                                    size="sm"
                                />
                            </x-slot>
                            
                            @foreach(Auth::user()->companies as $company)
                                <x-filament::dropdown.item
                                    wire:click="switchCompany({{ $company->id }})"
                                    :icon="$company->id === $this->getCompany()->id ? 'heroicon-o-check' : null"
                                >
                                    {{ $company->name }}
                                </x-filament::dropdown.item>
                            @endforeach
                        </x-filament::dropdown>
                    @endif
                </div>
            @endif
            
            {{-- Branch Context --}}
            @if($this->getBranch())
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-building-storefront class="w-5 h-5 text-gray-400" />
                    <span class="text-sm font-medium">{{ $this->getBranch()->name }}</span>
                    
                    @if($this->canSwitchBranch())
                        <x-filament::dropdown>
                            <x-slot name="trigger">
                                <x-filament::icon-button 
                                    icon="heroicon-o-chevron-down"
                                    size="sm"
                                />
                            </x-slot>
                            
                            @foreach($this->getCompany()->branches as $branch)
                                <x-filament::dropdown.item
                                    wire:click="switchBranch({{ $branch->id }})"
                                    :icon="$branch->id === $this->getBranch()->id ? 'heroicon-o-check' : null"
                                >
                                    {{ $branch->name }}
                                </x-filament::dropdown.item>
                            @endforeach
                        </x-filament::dropdown>
                    @endif
                </div>
            @elseif($this->getCompany() && $this->getCompany()->branches->count() > 0)
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                    <span class="text-sm text-gray-500">Keine Filiale ausgewählt</span>
                </div>
            @endif
            
            {{-- Quick Actions --}}
            <div class="flex items-center space-x-2">
                <x-filament::button
                    href="{{ route('filament.admin.resources.appointments.create') }}"
                    icon="heroicon-o-plus"
                    size="sm"
                    color="success"
                >
                    Neuer Termin
                </x-filament::button>
                
                <x-filament::dropdown>
                    <x-slot name="trigger">
                        <x-filament::button
                            icon="heroicon-o-squares-plus"
                            size="sm"
                            outlined
                        >
                            Schnellaktionen
                        </x-filament::button>
                    </x-slot>
                    
                    <x-filament::dropdown.item
                        href="{{ route('filament.admin.resources.customers.create') }}"
                        icon="heroicon-o-user-plus"
                    >
                        Neuer Kunde
                    </x-filament::dropdown.item>
                    
                    <x-filament::dropdown.item
                        href="{{ route('filament.admin.resources.calls.index') }}"
                        icon="heroicon-o-phone"
                    >
                        Letzte Anrufe
                    </x-filament::dropdown.item>
                    
                    <x-filament::dropdown.item
                        href="{{ route('filament.admin.pages.quick-setup-wizard') }}"
                        icon="heroicon-o-cog-6-tooth"
                    >
                        Schnelleinrichtung
                    </x-filament::dropdown.item>
                </x-filament::dropdown>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>