<x-filament-panels::page>
    <div class="space-y-6">
        {{-- System Information --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">System Information</h2>
            <div class="grid grid-cols-2 gap-4">
                @foreach($this->getSystemInfo() as $key => $value)
                    <div>
                        <span class="font-medium text-gray-600">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                        <span class="text-gray-900">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Dropdown Test --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Dropdown Test</h2>
            
            {{-- Test Filament Dropdown --}}
            <div class="mb-4" x-data="{ open: false }">
                <label class="block text-sm font-medium mb-2">Test Dropdown (Alpine.js)</label>
                <div class="relative">
                    <button 
                        @click="open = !open"
                        type="button" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg px-4 py-2 w-full md:w-auto flex items-center justify-between"
                    >
                        <span>Click to test dropdown</span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        x-transition
                        class="absolute z-50 mt-2 w-full md:w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                    >
                        <div class="py-1">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Option 1</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Option 2</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Option 3</a>
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Status: <span x-text="open ? 'Open' : 'Closed'"></span>
                </p>
            </div>
            
            {{-- Filament Actions Dropdown --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Filament Actions Dropdown</label>
                <x-filament::dropdown>
                    <x-slot name="trigger">
                        <x-filament::button>
                            Actions Menu
                        </x-filament::button>
                    </x-slot>
                    
                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item wire:click="testDropdown">
                            Test Action 1
                        </x-filament::dropdown.list.item>
                        
                        <x-filament::dropdown.list.item>
                            Test Action 2
                        </x-filament::dropdown.list.item>
                        
                        <x-filament::dropdown.list.item>
                            Test Action 3
                        </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
        </div>
        
        {{-- JavaScript Debug Console --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">JavaScript Console</h2>
            <div id="js-console" class="bg-gray-900 text-gray-100 p-4 rounded font-mono text-sm h-64 overflow-y-auto">
                <div>Initializing...</div>
            </div>
            
            <div class="mt-4 space-x-2">
                <x-filament::button size="sm" @click="checkAlpine()">
                    Check Alpine
                </x-filament::button>
                
                <x-filament::button size="sm" @click="checkLivewire()">
                    Check Livewire
                </x-filament::button>
                
                <x-filament::button size="sm" @click="findDropdowns()">
                    Find All Dropdowns
                </x-filament::button>
                
                <x-filament::button size="sm" @click="applyFixes()">
                    Apply Fixes
                </x-filament::button>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        const jsConsole = document.getElementById('js-console');
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-400' : 
                         type === 'success' ? 'text-green-400' : 
                         'text-gray-100';
            
            const entry = document.createElement('div');
            entry.className = color;
            entry.textContent = `[${timestamp}] ${message}`;
            jsConsole.appendChild(entry);
            jsConsole.scrollTop = jsConsole.scrollHeight;
        }
        
        // Initial checks
        document.addEventListener('DOMContentLoaded', function() {
            log('DOM loaded');
            
            // Check frameworks
            if (typeof Alpine !== 'undefined') {
                log('Alpine.js is loaded', 'success');
            } else {
                log('Alpine.js NOT loaded', 'error');
            }
            
            if (typeof Livewire !== 'undefined') {
                log('Livewire is loaded', 'success');
            } else {
                log('Livewire NOT loaded', 'error');
            }
            
            // Check for fixes
            if (window.adminPortalFixes) {
                log('Admin Portal Fixes loaded', 'success');
            }
            
            if (window.operationsCenterFixes) {
                log('Operations Center Fixes loaded', 'success');
            }
        });
        
        function checkAlpine() {
            if (typeof Alpine === 'undefined') {
                log('Alpine is not defined', 'error');
                return;
            }
            
            const components = document.querySelectorAll('[x-data]');
            log(`Found ${components.length} Alpine components`);
            
            let initialized = 0;
            components.forEach((el, i) => {
                if (el.__x) {
                    initialized++;
                }
            });
            
            log(`${initialized} of ${components.length} components initialized`, 
                initialized === components.length ? 'success' : 'error');
        }
        
        function checkLivewire() {
            if (typeof Livewire === 'undefined') {
                log('Livewire is not defined', 'error');
                return;
            }
            
            log('Livewire version: ' + (Livewire.version || 'Unknown'), 'success');
            
            // Test event system
            log('Testing Livewire events...');
            Livewire.on('test-event', () => {
                log('Livewire event received!', 'success');
            });
            Livewire.dispatch('test-event');
        }
        
        function findDropdowns() {
            const dropdowns = document.querySelectorAll('[x-data*="open"]');
            log(`Found ${dropdowns.length} potential dropdowns`);
            
            dropdowns.forEach((dd, i) => {
                const hasAlpine = !!dd.__x;
                const isOpen = hasAlpine ? dd.__x.$data.open : 'N/A';
                log(`Dropdown ${i + 1}: Alpine=${hasAlpine}, Open=${isOpen}`);
            });
            
            // Check Filament dropdowns
            const filamentDropdowns = document.querySelectorAll('.fi-dropdown');
            log(`Found ${filamentDropdowns.length} Filament dropdowns`);
        }
        
        function applyFixes() {
            log('Applying fixes...');
            
            if (window.adminPortalFixes) {
                window.adminPortalFixes.fixDropdowns();
                log('Admin portal fixes applied', 'success');
            }
            
            if (window.operationsCenterFixes) {
                window.operationsCenterFixes.fixDropdowns();
                log('Operations center fixes applied', 'success');
            }
            
            // Re-initialize uninitialized Alpine components
            if (typeof Alpine !== 'undefined') {
                document.querySelectorAll('[x-data]:not([data-alpine-initialized])').forEach(el => {
                    if (!el.__x) {
                        Alpine.initTree(el);
                        el.setAttribute('data-alpine-initialized', 'true');
                    }
                });
                log('Re-initialized Alpine components', 'success');
            }
        }
    </script>
    @endpush
</x-filament-panels::page>