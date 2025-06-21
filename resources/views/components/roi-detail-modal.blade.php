@props([
    'branchId' => null,
    'branchName' => '',
])

<div x-data="{ 
    open: false,
    loading: true,
    data: null,
    
    async loadDetails() {
        this.loading = true;
        this.open = true;
        
        // Simulate API call - replace with actual Livewire method
        try {
            const response = await fetch(`/api/branches/${this.branchId}/roi-details`);
            this.data = await response.json();
        } catch (error) {
            console.error('Error loading ROI details:', error);
        } finally {
            this.loading = false;
        }
    }
}" 
x-show="open" 
x-cloak
@keydown.escape.window="open = false"
class="fixed inset-0 z-50 overflow-y-auto">
    
    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
    
    {{-- Modal --}}
    <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
        
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block w-full transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:max-w-4xl sm:align-middle">
            
            {{-- Header --}}
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">
                        ROI-Details: {{ $branchName }}
                    </h3>
                    <button @click="open = false" class="rounded-md text-gray-400 hover:text-gray-500">
                        <x-heroicon-o-x-mark class="h-6 w-6" />
                    </button>
                </div>
            </div>
            
            {{-- Content --}}
            <div class="px-6 py-4">
                <div x-show="loading" class="flex h-64 items-center justify-center">
                    <div class="text-center">
                        <div class="inline-flex h-12 w-12 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
                        <p class="mt-2 text-sm text-gray-500">Lade Details...</p>
                    </div>
                </div>
                
                <div x-show="!loading && data" x-cloak>
                    {{-- Tabs --}}
                    <div x-data="{ activeTab: 'overview' }" class="space-y-6">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8">
                                <button @click="activeTab = 'overview'"
                                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                                    Übersicht
                                </button>
                                <button @click="activeTab = 'staff'"
                                        :class="activeTab === 'staff' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                                    Mitarbeiter
                                </button>
                                <button @click="activeTab = 'services'"
                                        :class="activeTab === 'services' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                                    Services
                                </button>
                                <button @click="activeTab = 'trends'"
                                        :class="activeTab === 'trends' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium">
                                    Trends
                                </button>
                            </nav>
                        </div>
                        
                        {{-- Tab Content --}}
                        <div>
                            {{-- Overview Tab --}}
                            <div x-show="activeTab === 'overview'" class="space-y-6">
                                {{-- KPI Cards --}}
                                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                    <div class="rounded-lg bg-blue-50 p-4">
                                        <p class="text-sm text-blue-600">ROI</p>
                                        <p class="text-2xl font-bold text-blue-900" x-text="data?.roi + '%'"></p>
                                    </div>
                                    <div class="rounded-lg bg-green-50 p-4">
                                        <p class="text-sm text-green-600">Umsatz</p>
                                        <p class="text-2xl font-bold text-green-900" x-text="'€' + data?.revenue"></p>
                                    </div>
                                    <div class="rounded-lg bg-yellow-50 p-4">
                                        <p class="text-sm text-yellow-600">Termine</p>
                                        <p class="text-2xl font-bold text-yellow-900" x-text="data?.appointments"></p>
                                    </div>
                                    <div class="rounded-lg bg-purple-50 p-4">
                                        <p class="text-sm text-purple-600">Ø Wert</p>
                                        <p class="text-2xl font-bold text-purple-900" x-text="'€' + data?.avgValue"></p>
                                    </div>
                                </div>
                                
                                {{-- Monthly Comparison Chart --}}
                                <div class="rounded-lg bg-gray-50 p-4">
                                    <h4 class="mb-4 font-medium text-gray-900">Monatlicher Verlauf</h4>
                                    <div class="h-48">
                                        <canvas id="monthly-roi-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Staff Tab --}}
                            <div x-show="activeTab === 'staff'" class="space-y-4">
                                <template x-for="staff in data?.staff" :key="staff.id">
                                    <div class="flex items-center justify-between rounded-lg border p-4">
                                        <div>
                                            <p class="font-medium" x-text="staff.name"></p>
                                            <p class="text-sm text-gray-500">
                                                <span x-text="staff.appointments"></span> Termine • 
                                                <span x-text="staff.completionRate + '%'"></span> Abschlussrate
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-semibold" x-text="'€' + staff.revenue"></p>
                                            <p class="text-sm text-gray-500">Umsatz</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            {{-- Services Tab --}}
                            <div x-show="activeTab === 'services'" class="space-y-4">
                                <template x-for="service in data?.services" :key="service.id">
                                    <div class="flex items-center justify-between rounded-lg border p-4">
                                        <div>
                                            <p class="font-medium" x-text="service.name"></p>
                                            <p class="text-sm text-gray-500">
                                                <span x-text="service.bookings"></span> Buchungen • 
                                                <span x-text="service.duration"></span> Min
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-semibold" x-text="'€' + service.revenue"></p>
                                            <p class="text-sm text-gray-500">
                                                €<span x-text="service.price"></span> pro Termin
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            {{-- Trends Tab --}}
                            <div x-show="activeTab === 'trends'" class="space-y-6">
                                {{-- Trend Indicators --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="rounded-lg border p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm text-gray-600">ROI-Trend</p>
                                            <div class="flex items-center" :class="data?.trends?.roi > 0 ? 'text-green-600' : 'text-red-600'">
                                                <template x-if="data?.trends?.roi > 0">
                                                    <x-heroicon-o-arrow-trending-up class="h-5 w-5" />
                                                </template>
                                                <template x-if="data?.trends?.roi <= 0">
                                                    <x-heroicon-o-arrow-trending-down class="h-5 w-5" />
                                                </template>
                                                <span class="ml-1 font-medium" x-text="Math.abs(data?.trends?.roi) + '%'"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-lg border p-4">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm text-gray-600">Umsatz-Trend</p>
                                            <div class="flex items-center" :class="data?.trends?.revenue > 0 ? 'text-green-600' : 'text-red-600'">
                                                <template x-if="data?.trends?.revenue > 0">
                                                    <x-heroicon-o-arrow-trending-up class="h-5 w-5" />
                                                </template>
                                                <template x-if="data?.trends?.revenue <= 0">
                                                    <x-heroicon-o-arrow-trending-down class="h-5 w-5" />
                                                </template>
                                                <span class="ml-1 font-medium" x-text="Math.abs(data?.trends?.revenue) + '%'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Recommendations --}}
                                <div class="rounded-lg bg-blue-50 p-4">
                                    <h4 class="mb-2 font-medium text-blue-900">Optimierungsempfehlungen</h4>
                                    <ul class="space-y-2 text-sm text-blue-700">
                                        <template x-for="recommendation in data?.recommendations" :key="recommendation">
                                            <li class="flex items-start">
                                                <x-heroicon-o-light-bulb class="mr-2 h-4 w-4 flex-shrink-0 text-blue-500" />
                                                <span x-text="recommendation"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="bg-gray-50 px-6 py-3">
                <div class="flex justify-end space-x-3">
                    <button @click="open = false" 
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Schließen
                    </button>
                    <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                        PDF exportieren
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>