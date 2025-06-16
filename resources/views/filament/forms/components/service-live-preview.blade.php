<div x-data="{
    serviceName: @entangle($getStatePath('name')),
    duration: @entangle($getStatePath('duration')),
    basePrice: @entangle($getStatePath('base_price')),
    description: @entangle($getStatePath('description')),
    isLoading: false,
    previewMode: 'customer',
    animationClass: 'animate-fade-in'
}" 
class="relative">
    <!-- Preview Mode Switcher -->
    <div class="absolute -top-12 right-0 flex gap-2 z-10">
        <button @click="previewMode = 'customer'; animationClass = 'animate-slide-right'" 
                :class="previewMode === 'customer' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:scale-105">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Kundenansicht
        </button>
        <button @click="previewMode = 'staff'; animationClass = 'animate-slide-left'" 
                :class="previewMode === 'staff' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300 hover:scale-105">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Mitarbeiteransicht
        </button>
    </div>

    <!-- Main Preview Container -->
    <div :class="animationClass" class="relative overflow-hidden rounded-2xl shadow-2xl bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700">
        
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239C92AC" fill-opacity="0.4"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        <!-- Customer View -->
        <div x-show="previewMode === 'customer'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <!-- Header Section -->
            <div class="relative px-8 py-6 bg-gradient-to-r from-blue-600/10 to-indigo-600/10 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="serviceName || 'Neuer Service'"></h3>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span x-text="duration + ' Min.'"></span>
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                ab <span x-text="basePrice"></span> €
                            </span>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-0 animate-ping bg-blue-400 rounded-full opacity-25"></div>
                        <div class="relative bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full p-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description Section -->
            <div class="px-8 py-6">
                <p class="text-gray-600 dark:text-gray-300 leading-relaxed" x-text="description || 'Servicebeschreibung wird hier angezeigt...'"></p>
            </div>

            <!-- Features Grid -->
            <div class="px-8 pb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-blue-600 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Schnellbuchung</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">24/7 verfügbar</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20">
                        <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-emerald-600 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Garantiert</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Beste Qualität</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Button -->
            <div class="px-8 pb-8">
                <button class="w-full relative overflow-hidden group bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl">
                    <span class="relative z-10">Jetzt buchen</span>
                    <div class="absolute inset-0 -top-2 -bottom-2 bg-gradient-to-r from-blue-400 to-indigo-400 opacity-0 group-hover:opacity-100 blur transition-opacity duration-300"></div>
                </button>
            </div>
        </div>

        <!-- Staff View -->
        <div x-show="previewMode === 'staff'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Mitarbeiter-Dashboard</h3>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 rounded-full text-sm font-medium">
                        Interne Ansicht
                    </span>
                </div>

                <!-- Service Info Cards -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Durchschnittliche Dauer</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="duration + ' Min.'"></div>
                        <div class="mt-2 flex items-center text-xs text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            +5% diese Woche
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Basispreis</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="basePrice + ' €'"></div>
                        <div class="mt-2 flex items-center text-xs text-blue-600 dark:text-blue-400">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Optimal kalkuliert
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Schnellzugriff</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <button class="p-3 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 transition-colors group">
                            <svg class="w-5 h-5 mx-auto text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1 block">Kalender</span>
                        </button>
                        <button class="p-3 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-emerald-500 dark:hover:border-emerald-400 transition-colors group">
                            <svg class="w-5 h-5 mx-auto text-gray-600 dark:text-gray-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1 block">Protokoll</span>
                        </button>
                        <button class="p-3 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-purple-500 dark:hover:border-purple-400 transition-colors group">
                            <svg class="w-5 h-5 mx-auto text-gray-600 dark:text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="text-xs text-gray-600 dark:text-gray-400 mt-1 block">Statistik</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Update Indicator -->
    <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
        <span>Live-Vorschau wird automatisch aktualisiert</span>
    </div>
</div>

<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slide-right {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slide-left {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.5s ease-out;
    }

    .animate-slide-right {
        animation: slide-right 0.3s ease-out;
    }

    .animate-slide-left {
        animation: slide-left 0.3s ease-out;
    }
</style>
