{{-- Compact Filter Controls --}}
<div class="flex flex-wrap items-center gap-3">
    {{-- Date Filter Dropdown --}}
    <div class="relative" x-data="{ showDateFilter: false }">
        <button 
            @click="showDateFilter = !showDateFilter"
            @click.away="showDateFilter = false"
            type="button"
            class="px-3 py-2 text-sm font-medium bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md hover:border-gray-400 dark:hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-150 flex items-center gap-2"
        >
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-gray-700 dark:text-gray-200">
                {{ $this->getPeriodOptions()[$globalFilters['period']]['label'] ?? 'Heute' }}
            </span>
            <svg class="w-4 h-4 text-gray-400 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        
        {{-- Date Options Dropdown --}}
        <div 
            x-show="showDateFilter"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-10 mt-1 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700 py-1"
        >
            @foreach($this->getPeriodOptions() as $value => $option)
                <button 
                    wire:click="$set('globalFilters.period', '{{ $value }}')"
                    @click="showDateFilter = false"
                    class="w-full px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $globalFilters['period'] === $value ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-700 dark:text-gray-300' }}"
                >
                    {{ $option['label'] }}
                </button>
            @endforeach
        </div>
    </div>
    
    {{-- Branch Filter --}}
    @if(count($this->getBranches()) > 1)
        <select 
            wire:model.live="globalFilters.branch_id"
            class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        >
            <option value="">Alle Filialen</option>
            @foreach($this->getBranches() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    @endif
    
    {{-- Staff Filter --}}
    @if(count($this->getStaff()) > 0)
        <select 
            wire:model.live="globalFilters.staff_id"
            class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        >
            <option value="">Alle Mitarbeiter</option>
            @foreach($this->getStaff() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    @endif
    
    {{-- Service Filter --}}
    @if(count($this->getServices()) > 0)
        <select 
            wire:model.live="globalFilters.service_id"
            class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        >
            <option value="">Alle Services</option>
            @foreach($this->getServices() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    @endif
    
    {{-- Action Buttons --}}
    <div class="flex items-center gap-2 ml-auto">
        @if($this->getActiveFilterCount() > 0)
            <span class="px-2 py-1 text-xs font-medium text-primary-700 bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 rounded-full">
                {{ $this->getActiveFilterCount() }} Filter aktiv
            </span>
        @endif
        
        <button 
            wire:click="resetGlobalFilters"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            title="Filter zurücksetzen"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>
</div>

