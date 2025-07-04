<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filament v3 Debug</title>
    @filamentStyles
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Filament v3 Debug Page</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- JavaScript Status -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">JavaScript Status</h2>
                <div id="js-status" class="space-y-2">
                    <p>Checking...</p>
                </div>
            </div>
            
            <!-- Test Dropdowns -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Test Dropdowns</h2>
                
                <!-- Basic Alpine Dropdown -->
                <div class="mb-4">
                    <h3 class="font-medium mb-2">Basic Alpine Dropdown</h3>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="px-4 py-2 bg-blue-500 text-white rounded">
                            Toggle Dropdown
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute mt-2 w-48 bg-white border rounded shadow-lg">
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Option 1</a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Option 2</a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Option 3</a>
                        </div>
                    </div>
                </div>
                
                <!-- Filament Style Dropdown -->
                <div class="mb-4">
                    <h3 class="font-medium mb-2">Filament Style Dropdown</h3>
                    <div class="fi-dropdown" x-data="dropdown">
                        <button x-ref="button" @click="toggle" class="fi-dropdown-trigger px-4 py-2 bg-gray-500 text-white rounded">
                            Filament Dropdown
                        </button>
                        <div x-ref="panel" x-show="open" @click.away="close" class="fi-dropdown-panel absolute mt-2 w-48 bg-white border rounded shadow-lg">
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Item 1</a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Item 2</a>
                            <a href="#" class="block px-4 py-2 hover:bg-gray-100">Item 3</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Console Output -->
            <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
                <h2 class="text-xl font-semibold mb-4">Console Output</h2>
                <div id="console-output" class="bg-gray-900 text-green-400 p-4 rounded font-mono text-sm overflow-auto max-h-96">
                    <p>Waiting for output...</p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
                <h2 class="text-xl font-semibold mb-4">Debug Actions</h2>
                <div class="flex gap-4 flex-wrap">
                    <button onclick="runDebug()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Run Debug
                    </button>
                    <button onclick="clearConsole()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Clear Console
                    </button>
                    <button onclick="patchDropdowns()" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        Patch Dropdowns
                    </button>
                    <button onclick="checkAlpine()" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                        Check Alpine
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    @filamentScripts(withCore: true)
    @vite(['resources/js/filament-v3-fixes.js', 'resources/js/app-filament-compatible.js'])
    
    <script>
        let consoleOutput = [];
        const maxConsoleLines = 100;
        
        // Override console methods to capture output
        const originalLog = console.log;
        const originalWarn = console.warn;
        const originalError = console.error;
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addToConsole('log', args);
        };
        
        console.warn = function(...args) {
            originalWarn.apply(console, args);
            addToConsole('warn', args);
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addToConsole('error', args);
        };
        
        function addToConsole(type, args) {
            const message = args.map(arg => {
                if (typeof arg === 'object') {
                    return JSON.stringify(arg, null, 2);
                }
                return String(arg);
            }).join(' ');
            
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-400' : type === 'warn' ? 'text-yellow-400' : 'text-green-400';
            
            consoleOutput.push(`<span class="${color}">[${timestamp}] ${type.toUpperCase()}: ${message}</span>`);
            
            if (consoleOutput.length > maxConsoleLines) {
                consoleOutput.shift();
            }
            
            updateConsoleDisplay();
        }
        
        function updateConsoleDisplay() {
            const output = document.getElementById('console-output');
            if (output) {
                output.innerHTML = consoleOutput.join('<br>') || '<p>No output yet...</p>';
                output.scrollTop = output.scrollHeight;
            }
        }
        
        function clearConsole() {
            consoleOutput = [];
            updateConsoleDisplay();
        }
        
        function updateJsStatus() {
            const status = document.getElementById('js-status');
            const checks = [
                { name: 'Alpine.js', available: !!window.Alpine, version: window.Alpine?.version },
                { name: 'Livewire', available: !!window.Livewire },
                { name: 'FilamentAlpine', available: !!window.FilamentAlpine },
                { name: 'Dropdown Manager', available: !!window.dropdownManager },
                { name: 'Filament V3 Fixes', available: !!window.FilamentV3Fixes },
                { name: 'Searchable Select Fix', available: !!window.FilamentSearchableSelectFix }
            ];
            
            status.innerHTML = checks.map(check => {
                const statusClass = check.available ? 'text-green-600' : 'text-red-600';
                const statusText = check.available ? '✓' : '✗';
                const version = check.version ? ` (v${check.version})` : '';
                return `<p><span class="${statusClass}">${statusText}</span> ${check.name}${version}</p>`;
            }).join('');
        }
        
        function runDebug() {
            console.log('=== Running Filament v3 Debug ===');
            
            if (window.debugFilament) {
                window.debugFilament();
            } else {
                console.warn('debugFilament function not available');
            }
            
            // Check for dropdowns
            const dropdowns = document.querySelectorAll('[x-data*="dropdown"], .fi-dropdown');
            console.log(`Found ${dropdowns.length} dropdowns`);
            
            // Check for searchable selects
            const searchableSelects = document.querySelectorAll('.fi-fo-select:has(input[x-ref="searchInput"])');
            console.log(`Found ${searchableSelects.length} searchable selects`);
            
            // Check for errors
            const errors = document.querySelectorAll('.error, [wire\\:error]');
            console.log(`Found ${errors.length} error elements`);
            
            // Check Alpine components
            let alpineComponents = 0;
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el._x_dataStack) alpineComponents++;
            });
            console.log(`Found ${alpineComponents} initialized Alpine components`);
        }
        
        function patchDropdowns() {
            console.log('Manually patching dropdowns...');
            
            if (window.FilamentV3Fixes) {
                window.FilamentV3Fixes.patchDropdowns();
                console.log('Dropdown patch applied');
            }
            
            if (window.FilamentSearchableSelectFix) {
                window.FilamentSearchableSelectFix.patch();
                console.log('Searchable select patch applied');
            }
        }
        
        function checkAlpine() {
            console.log('=== Alpine.js Check ===');
            
            if (!window.Alpine) {
                console.error('Alpine.js not found!');
                return;
            }
            
            console.log('Alpine version:', window.Alpine.version);
            console.log('Alpine store:', window.Alpine.store);
            
            // List all Alpine components
            const components = [];
            document.querySelectorAll('[x-data]').forEach((el, index) => {
                const data = el.getAttribute('x-data');
                components.push(`Component ${index + 1}: ${data.substring(0, 50)}...`);
            });
            
            console.log('Alpine components found:', components.length);
            components.forEach(comp => console.log(comp));
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            updateJsStatus();
            console.log('Debug page loaded');
            
            // Monitor for changes
            setInterval(updateJsStatus, 2000);
        });
        
        // Capture global errors
        window.addEventListener('error', function(event) {
            console.error('Global error:', event.error?.message || event.message);
        });
    </script>
</body>
</html>