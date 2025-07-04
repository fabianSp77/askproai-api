@php
    try {
        $branchContext = app(\App\Services\BranchContextManager::class);
        $currentBranch = $branchContext->getCurrentBranch();
        $isAllBranches = $branchContext->isAllBranchesView();
        $branches = $branchContext->getBranchesForUser();
    } catch (\Exception $e) {
        $currentBranch = null;
        $isAllBranches = false;
        $branches = collect();
    }
    
    // Generate unique ID for this instance
    $componentId = 'branch-switcher-' . uniqid();
@endphp

{{-- Ultra Simple Branch Switcher - 100% Working Solution --}}
<div id="{{ $componentId }}" class="relative branch-selector-dropdown">
    {{-- Trigger Button --}}
    <button
        type="button"
        onclick="window.toggleBranchDropdown('{{ $componentId }}')"
        class="fi-user-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-primary-600 disabled:pointer-events-none disabled:opacity-70 -ms-2 h-9 w-9 text-sm text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-400 dark:focus-visible:ring-primary-500"
        aria-label="{{ __('Filiale wechseln') }}"
        aria-expanded="false"
    >
        <span class="sr-only">{{ __('Filiale wechseln') }}</span>
        <div class="relative">
            <x-filament::icon 
                icon="heroicon-o-building-office-2" 
                class="h-5 w-5 transition-transform duration-200"
            />
            @if(!$isAllBranches && $currentBranch)
                <div class="absolute -bottom-1 -right-1 h-2 w-2 rounded-full bg-primary-600 dark:bg-primary-400 animate-pulse"></div>
            @endif
        </div>
    </button>

    {{-- Dropdown Panel --}}
    <div
        id="{{ $componentId }}-panel"
        class="hidden absolute end-0 z-[99999] mt-2 w-72 origin-top-right divide-y divide-gray-100 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:divide-gray-700 dark:bg-gray-800 dark:ring-white/10"
        role="menu"
        style="position: fixed !important;"
    >
        {{-- Search Input (shown when more than 5 branches) --}}
        @if($branches && $branches->count() > 5)
        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
            <div class="relative">
                <x-filament::icon 
                    icon="heroicon-m-magnifying-glass" 
                    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"
                />
                <input
                    type="text"
                    id="{{ $componentId }}-search"
                    placeholder="{{ __('Filiale suchen...') }}"
                    class="w-full pl-9 pr-3 py-2 text-sm border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    onkeyup="window.filterBranches('{{ $componentId }}')"
                />
            </div>
        </div>
        @endif

        {{-- Current Selection Info --}}
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                {{ __('Aktuelle Ansicht') }}
            </p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                @if($isAllBranches)
                    <x-filament::icon icon="heroicon-m-building-office" class="h-4 w-4" />
                    {{ __('Alle Filialen') }}
                @elseif($currentBranch)
                    <x-filament::icon icon="heroicon-m-building-office-2" class="h-4 w-4" />
                    {{ $currentBranch->name }}
                @else
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4 text-warning-500" />
                    {{ __('Keine Filiale gewählt') }}
                @endif
            </p>
        </div>

        {{-- Branch List --}}
        <div class="max-h-60 overflow-y-auto py-1" id="{{ $componentId }}-branches">
            {{-- All Branches Option --}}
            <a
                href="{{ request()->url() . '?branch=all' }}"
                class="branch-item group flex items-center px-4 py-2 text-sm transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $isAllBranches ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}"
                role="menuitem"
                data-branch-name="{{ strtolower(__('Alle Filialen anzeigen')) }}"
            >
                <x-filament::icon 
                    icon="heroicon-m-building-office" 
                    class="mr-3 h-5 w-5 {{ $isAllBranches ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' }}"
                />
                <span class="flex-1">{{ __('Alle Filialen anzeigen') }}</span>
                @if($isAllBranches)
                    <x-filament::icon 
                        icon="heroicon-m-check" 
                        class="ml-2 h-4 w-4 text-primary-600 dark:text-primary-400"
                    />
                @endif
            </a>

            {{-- Divider --}}
            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

            {{-- Individual Branches --}}
            @foreach($branches as $branch)
                <a
                    href="{{ request()->url() . '?branch=' . $branch->id }}"
                    class="branch-item group flex items-center px-4 py-2 text-sm transition-colors duration-150 hover:bg-gray-100 dark:hover:bg-gray-700 {{ !$isAllBranches && $currentBranch && $currentBranch->id === $branch->id ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}"
                    role="menuitem"
                    data-branch-name="{{ strtolower($branch->name) }}"
                >
                    <x-filament::icon 
                        icon="heroicon-m-building-office-2" 
                        class="mr-3 h-5 w-5 {{ !$isAllBranches && $currentBranch && $currentBranch->id === $branch->id ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400' }}"
                    />
                    <span class="flex-1">{{ $branch->name }}</span>
                    @if(!$isAllBranches && $currentBranch && $currentBranch->id === $branch->id)
                        <x-filament::icon 
                            icon="heroicon-m-check" 
                            class="ml-2 h-4 w-4 text-primary-600 dark:text-primary-400"
                        />
                    @endif
                </a>
            @endforeach

            {{-- No Results Message (hidden by default) --}}
            <div id="{{ $componentId }}-no-results" class="hidden px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                {{ __('Keine Filialen gefunden') }}
            </div>
        </div>

        {{-- Footer with Branch Count --}}
        @if($branches && $branches->count() > 0)
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400">
            <span id="{{ $componentId }}-count">{{ $branches->count() }}</span> {{ __('von') }} {{ $branches->count() }} {{ __('Filialen') }}
        </div>
        @endif
    </div>
</div>

{{-- Ultra Simple JavaScript - No Dependencies --}}
<script>
(function() {
    'use strict';
    
    // Global functions for branch dropdown
    window.toggleBranchDropdown = function(componentId) {
        const dropdown = document.getElementById(componentId + '-panel');
        const button = document.querySelector('#' + componentId + ' button');
        
        if (dropdown.classList.contains('hidden')) {
            // Open dropdown
            dropdown.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
            
            // Position dropdown
            positionDropdown(componentId);
            
            // Add click outside listener
            setTimeout(() => {
                document.addEventListener('click', closeBranchDropdownHandler);
            }, 100);
        } else {
            // Close dropdown
            closeBranchDropdown();
        }
    };
    
    window.filterBranches = function(componentId) {
        const searchInput = document.getElementById(componentId + '-search');
        const searchTerm = searchInput.value.toLowerCase();
        const branchItems = document.querySelectorAll('#' + componentId + '-branches .branch-item');
        const noResults = document.getElementById(componentId + '-no-results');
        let visibleCount = 0;
        
        branchItems.forEach(item => {
            const branchName = item.getAttribute('data-branch-name');
            if (branchName.includes(searchTerm)) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Update count
        const countElement = document.getElementById(componentId + '-count');
        if (countElement) {
            countElement.textContent = visibleCount;
        }
        
        // Show/hide no results message
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    };
    
    function positionDropdown(componentId) {
        const button = document.querySelector('#' + componentId + ' button');
        const dropdown = document.getElementById(componentId + '-panel');
        
        const buttonRect = button.getBoundingClientRect();
        
        // Set position
        dropdown.style.position = 'fixed';
        dropdown.style.top = (buttonRect.bottom + 8) + 'px';
        dropdown.style.right = (window.innerWidth - buttonRect.right) + 'px';
        dropdown.style.left = 'auto';
        
        // Check if dropdown goes off screen
        const dropdownRect = dropdown.getBoundingClientRect();
        if (dropdownRect.bottom > window.innerHeight) {
            // Position above button
            dropdown.style.top = 'auto';
            dropdown.style.bottom = (window.innerHeight - buttonRect.top + 8) + 'px';
        }
        
        if (dropdownRect.left < 0) {
            dropdown.style.left = '8px';
            dropdown.style.right = 'auto';
        }
    }
    
    function closeBranchDropdown() {
        const dropdowns = document.querySelectorAll('.branch-selector-dropdown [id$="-panel"]');
        dropdowns.forEach(dropdown => {
            if (!dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
                const button = dropdown.parentElement.querySelector('button');
                if (button) {
                    button.setAttribute('aria-expanded', 'false');
                }
            }
        });
        
        document.removeEventListener('click', closeBranchDropdownHandler);
    }
    
    function closeBranchDropdownHandler(event) {
        const isClickInside = event.target.closest('.branch-selector-dropdown');
        if (!isClickInside) {
            closeBranchDropdown();
        }
    }
    
    // ESC key handler
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeBranchDropdown();
        }
    });
    
    // Close dropdowns when navigating
    window.addEventListener('beforeunload', closeBranchDropdown);
})();
</script>

<style>
/* Ensure highest z-index */
.branch-selector-dropdown [id$="-panel"] {
    z-index: 999999 !important;
}
</style>