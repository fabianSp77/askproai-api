<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flowbite Components Test Page</title>
    
    <!-- Flowbite CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .test-section {
            margin: 2rem 0;
            padding: 2rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
        }
        .test-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-success {
            background-color: #10b981;
            color: white;
        }
        .status-error {
            background-color: #ef4444;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">🧪 Flowbite Components Test Suite</h1>
        
        <!-- Test Status Display -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Initialization Status</h2>
            <div id="status-display" class="space-y-2">
                <div>Alpine.js: <span id="alpine-status" class="status-badge status-error">Not Loaded</span></div>
                <div>Flowbite: <span id="flowbite-status" class="status-badge status-error">Not Loaded</span></div>
                <div>Components: <span id="component-count" class="text-gray-600">Counting...</span></div>
            </div>
        </div>
        
        <!-- Test 1: Alpine.js Pricing Toggle -->
        <div class="test-section bg-white">
            <h2 class="test-title">Test 1: Alpine.js Pricing Toggle</h2>
            <x-flowbite.react-blocks.marketing-ui.pricing-table-toggle />
        </div>
        
        <!-- Test 2: Flowbite Modal -->
        <div class="test-section bg-white">
            <h2 class="test-title">Test 2: Flowbite Modal</h2>
            
            <!-- Modal toggle -->
            <button data-modal-target="test-modal" data-modal-toggle="test-modal" class="block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                Open Modal
            </button>

            <!-- Main modal -->
            <div id="test-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 w-full max-w-2xl max-h-full">
                    <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Test Modal
                            </h3>
                            <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="test-modal">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                            </button>
                        </div>
                        <div class="p-4 md:p-5 space-y-4">
                            <p>✅ If you can see this modal, Flowbite is working!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test 3: Flowbite Dropdown -->
        <div class="test-section bg-white">
            <h2 class="test-title">Test 3: Flowbite Dropdown</h2>
            
            <button id="dropdownDefaultButton" data-dropdown-toggle="dropdown" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" type="button">
                Dropdown button
                <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                </svg>
            </button>

            <div id="dropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700">
                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownDefaultButton">
                    <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Option 1</a></li>
                    <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Option 2</a></li>
                    <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Option 3</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Test 4: Alpine.js Counter -->
        <div class="test-section bg-white">
            <h2 class="test-title">Test 4: Alpine.js Simple Counter</h2>
            <div x-data="{ count: 0 }" class="p-4 border rounded">
                <p class="mb-4">Count: <span x-text="count" class="font-bold text-2xl"></span></p>
                <button @click="count++" class="bg-green-500 text-white px-4 py-2 rounded mr-2">Increment</button>
                <button @click="count--" class="bg-red-500 text-white px-4 py-2 rounded">Decrement</button>
            </div>
        </div>
    </div>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Flowbite JS -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    
    <!-- Enhanced Initialization -->
    <script src="/js/flowbite-preview-init.js"></script>
    
    <script>
        // Status monitoring
        function updateStatus() {
            const alpineLoaded = typeof Alpine !== 'undefined';
            const flowbiteLoaded = typeof initFlowbite !== 'undefined';
            
            // Update badges
            if (alpineLoaded) {
                document.getElementById('alpine-status').className = 'status-badge status-success';
                document.getElementById('alpine-status').textContent = 'Loaded ✓';
            }
            
            if (flowbiteLoaded) {
                document.getElementById('flowbite-status').className = 'status-badge status-success';
                document.getElementById('flowbite-status').textContent = 'Loaded ✓';
            }
            
            // Count components
            const counts = {
                alpine: document.querySelectorAll('[x-data]').length,
                modals: document.querySelectorAll('[data-modal-toggle]').length,
                dropdowns: document.querySelectorAll('[data-dropdown-toggle]').length,
                tooltips: document.querySelectorAll('[data-tooltip-target]').length
            };
            
            document.getElementById('component-count').textContent = 
                `Alpine: ${counts.alpine}, Modals: ${counts.modals}, Dropdowns: ${counts.dropdowns}`;
        }
        
        // Update status on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(updateStatus, 500);
        });
        
        // Listen for initialization event
        window.addEventListener('flowbite-preview-ready', function(e) {
            console.log('✅ All components initialized:', e.detail);
            updateStatus();
        });
        
        // Manual test helper
        window.testAll = function() {
            console.log('Testing all components...');
            
            // Test Alpine
            const alpineComponents = document.querySelectorAll('[x-data]');
            console.log(`Found ${alpineComponents.length} Alpine components`);
            
            // Test Flowbite
            if (typeof initFlowbite !== 'undefined') {
                initFlowbite();
                console.log('Flowbite re-initialized');
            }
            
            updateStatus();
        };
        
        console.log('Test page loaded. Use window.testAll() to test all components.');
    </script>
</body>
</html>