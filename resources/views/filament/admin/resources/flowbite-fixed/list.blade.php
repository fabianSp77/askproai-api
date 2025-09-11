<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Enhanced Header with Search -->
        <div class="bg-gradient-to-r from-blue-600 to-cyan-600 rounded-xl shadow-lg p-6 text-white">
            <h1 class="text-3xl font-bold mb-2">Flowbite Pro Component Library</h1>
            <p class="text-blue-100 mb-4">Browse and preview all available UI components</p>
            
            <!-- Search Bar -->
            <div class="relative max-w-xl">
                <input 
                    type="text" 
                    x-data
                    x-on:input="
                        let search = $event.target.value.toLowerCase();
                        document.querySelectorAll('.component-card').forEach(card => {
                            let name = card.dataset.name.toLowerCase();
                            let category = card.dataset.category.toLowerCase();
                            card.style.display = (name.includes(search) || category.includes(search)) ? '' : 'none';
                        });
                    "
                    placeholder="Search components..." 
                    class="w-full px-4 py-2 pl-10 pr-4 text-gray-900 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
        
        <!-- Enhanced Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all p-6 border-t-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ count($components) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Components</div>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all p-6 border-t-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ count(array_unique(array_column($components, 'category'))) }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Categories</div>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all p-6 border-t-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ count(array_filter($components, fn($c) => $c['type'] === 'blade')) }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Blade Components</div>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all p-6 border-t-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ count(array_filter($components, fn($c) => str_contains($c['type'], 'react'))) }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">React Converted</div>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="flex flex-wrap gap-2" x-data="{ activeFilter: 'all' }">
            <button 
                @click="activeFilter = 'all'; document.querySelectorAll('.component-card').forEach(el => el.style.display = '')"
                :class="activeFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg font-medium transition-colors"
            >
                All Components
            </button>
            <button 
                @click="activeFilter = 'blade'; document.querySelectorAll('.component-card').forEach(el => el.dataset.type === 'blade' ? el.style.display = '' : el.style.display = 'none')"
                :class="activeFilter === 'blade' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg font-medium transition-colors"
            >
                Blade
            </button>
            <button 
                @click="activeFilter = 'alpine'; document.querySelectorAll('.component-card').forEach(el => el.dataset.type === 'alpine' ? el.style.display = '' : el.style.display = 'none')"
                :class="activeFilter === 'alpine' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg font-medium transition-colors"
            >
                Alpine.js
            </button>
            <button 
                @click="activeFilter = 'livewire'; document.querySelectorAll('.component-card').forEach(el => el.dataset.type === 'livewire' ? el.style.display = '' : el.style.display = 'none')"
                :class="activeFilter === 'livewire' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg font-medium transition-colors"
            >
                Livewire
            </button>
            <button 
                @click="activeFilter = 'react'; document.querySelectorAll('.component-card').forEach(el => el.dataset.type === 'react-converted' ? el.style.display = '' : el.style.display = 'none')"
                :class="activeFilter === 'react' ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                class="px-4 py-2 rounded-lg font-medium transition-colors"
            >
                React Converted
            </button>
        </div>
        
        <!-- Enhanced Components Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6"
            x-data="{ 
                showModal: false, 
                selectedComponent: null,
                isLoading: false,
                showPreview(component) {
                    this.selectedComponent = component;
                    this.showModal = true;
                    this.isLoading = true;
                    // Hide loading after iframe loads
                    setTimeout(() => { this.isLoading = false; }, 1000);
                }
            }"
        >
            @foreach($components as $index => $component)
                <div 
                    class="component-card bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden group cursor-pointer"
                    data-name="{{ $component['name'] }}"
                    data-category="{{ $component['category'] }}"
                    data-type="{{ $component['type'] }}"
                    @click="showPreview({{ json_encode($component) }})"
                >
                    <!-- Component Preview Area -->
                    <div class="h-32 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-grid-gray-200 dark:bg-grid-gray-700 opacity-10"></div>
                        
                        <!-- Icon based on type -->
                        @if($component['type'] == 'alpine')
                            <div class="text-green-500 opacity-20 group-hover:opacity-40 transition-opacity">
                                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                </svg>
                            </div>
                        @elseif($component['type'] == 'livewire')
                            <div class="text-purple-500 opacity-20 group-hover:opacity-40 transition-opacity">
                                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M13 3L3 14l9 9 10-11L13 3zm0 5.83L18.17 14 13 19.17 7.83 14 13 8.83z"/>
                                </svg>
                            </div>
                        @else
                            <div class="text-blue-500 opacity-20 group-hover:opacity-40 transition-opacity">
                                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14.5 3L12 5.5 9.5 3 2 10.5 4.5 13 7 10.5 9.5 13 12 10.5 14.5 13 17 10.5 19.5 13 22 10.5 14.5 3zM12 7.29L13.71 9 12 10.71 10.29 9 12 7.29zM5.41 10.5L7 8.91 8.59 10.5 7 12.09 5.41 10.5zm13.18 0L17 12.09 15.41 10.5 17 8.91l1.59 1.59z"/>
                                </svg>
                            </div>
                        @endif
                        
                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-blue-600 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                        
                        <!-- View Button -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Preview
                            </span>
                        </div>
                    </div>
                    
                    <!-- Component Details -->
                    <div class="p-4">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base mb-1 truncate" title="{{ $component['name'] }}">
                            {{ $component['name'] }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 truncate" title="{{ $component['category'] }}">
                            {{ $component['category'] }}
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                @if($component['type'] == 'alpine') 
                                    bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($component['type'] == 'livewire') 
                                    bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                @elseif($component['type'] == 'react-converted') 
                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else 
                                    bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @endif">
                                {{ ucfirst($component['type']) }}
                            </span>
                            <span class="text-xs text-gray-400 font-medium">{{ $component['size'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
            
            <!-- Preview Modal -->
            <div 
                x-show="showModal"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title" 
                role="dialog" 
                aria-modal="true"
            >
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Background overlay -->
                    <div 
                        x-show="showModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        @click="showModal = false"
                    ></div>

                    <!-- Modal panel -->
                    <div 
                        x-show="showModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full"
                    >
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedComponent ? selectedComponent.name : ''">
                                </h3>
                                <button 
                                    @click="showModal = false"
                                    type="button" 
                                    class="text-gray-400 hover:text-gray-500 focus:outline-none"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="mt-3">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    <span x-text="selectedComponent ? 'Category: ' + selectedComponent.category + ' | Type: ' + selectedComponent.type + ' | Size: ' + selectedComponent.size : ''"></span>
                                </p>
                                
                                <!-- Component Preview Frame -->
                                <div class="border-2 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden relative">
                                    <!-- Loading Indicator -->
                                    <div x-show="isLoading" class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-10">
                                        <div class="flex flex-col items-center">
                                            <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">Loading component...</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Iframe -->
                                    <iframe 
                                        :src="selectedComponent ? '/admin/flowbite-preview?path=' + encodeURIComponent(selectedComponent.path) : 'about:blank'"
                                        @load="isLoading = false"
                                        class="w-full h-[600px] bg-white"
                                        frameborder="0"
                                    ></iframe>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                type="button" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                                @click="if(selectedComponent) { navigator.clipboard.writeText(selectedComponent.path); alert('Path copied!'); }"
                            >
                                Copy Path
                            </button>
                            <button 
                                @click="showModal = false"
                                type="button" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        @if(empty($components))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No components found</h3>
                <p class="text-gray-500 dark:text-gray-400">Components will appear here once they are added to the system.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
