<div x-data="{
    basePrice: @entangle($getStatePath('base_price')),
    peakSurcharge: @entangle($getStatePath('peak_surcharge')),
    weekendSurcharge: @entangle($getStatePath('weekend_surcharge')),
    
    priceMatrix: [],
    selectedCell: null,
    
    init() {
        this.calculateMatrix();
        this.$watch('basePrice', () => this.calculateMatrix());
        this.$watch('peakSurcharge', () => this.calculateMatrix());
        this.$watch('weekendSurcharge', () => this.calculateMatrix());
    },
    
    calculateMatrix() {
        const base = parseFloat(this.basePrice) || 0;
        const peak = parseFloat(this.peakSurcharge) || 0;
        const weekend = parseFloat(this.weekendSurcharge) || 0;
        
        this.priceMatrix = [
            { 
                label: 'Regulär', 
                weekday: base, 
                weekend: base + weekend,
                color: 'blue'
            },
            { 
                label: 'Stoßzeit', 
                weekday: base + peak, 
                weekend: base + peak + weekend,
                color: 'purple'
            },
            { 
                label: 'Früh/Spät', 
                weekday: base - (base * 0.1), 
                weekend: (base + weekend) - ((base + weekend) * 0.1),
                color: 'emerald'
            }
        ];
    }
}" class="space-y-4">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Dynamische Preismatrix</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Automatische Preisberechnung</p>
            </div>
        </div>
    </div>

    <!-- Price Matrix Grid -->
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl p-1 shadow-inner">
        <div class="grid grid-cols-3 gap-1">
            <!-- Header Row -->
            <div class="bg-white dark:bg-gray-700 rounded-tl-lg p-3"></div>
            <div class="bg-white dark:bg-gray-700 p-3 flex items-center justify-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Wochentag</span>
            </div>
            <div class="bg-white dark:bg-gray-700 rounded-tr-lg p-3 flex items-center justify-center">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Wochenende</span>
            </div>
            
            <!-- Data Rows -->
            <template x-for="(row, index) in priceMatrix" :key="index">
                <>
                    <div class="bg-white dark:bg-gray-700 p-3 flex items-center" 
                         :class="index === priceMatrix.length - 1 ? 'rounded-bl-lg' : ''">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="row.label"></span>
                    </div>
                    
                    <div @click="selectedCell = row.label + '_weekday'"
                         :class="[
                             'relative p-4 cursor-pointer transition-all duration-300 overflow-hidden group',
                             selectedCell === row.label + '_weekday' 
                                 ? 'bg-gradient-to-br from-' + row.color + '-500 to-' + row.color + '-600 text-white scale-105 shadow-lg rounded-lg' 
                                 : 'bg-white dark:bg-gray-700 hover:shadow-md'
                         ]">
                        <div class="relative z-10">
                            <div class="text-2xl font-bold" x-text="row.weekday.toFixed(2) + '€'"></div>
                            <div class="text-xs opacity-75" x-text="'(' + ((row.weekday / basePrice - 1) * 100).toFixed(0) + '%)'"></div>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-20 transition-opacity"
                             :class="'from-' + row.color + '-400 to-' + row.color + '-600'"></div>
                    </div>
                    
                    <div @click="selectedCell = row.label + '_weekend'"
                         :class="[
                             'relative p-4 cursor-pointer transition-all duration-300 overflow-hidden group',
                             selectedCell === row.label + '_weekend' 
                                 ? 'bg-gradient-to-br from-' + row.color + '-500 to-' + row.color + '-600 text-white scale-105 shadow-lg rounded-lg' 
                                 : 'bg-white dark:bg-gray-700 hover:shadow-md',
                             index === priceMatrix.length - 1 ? 'rounded-br-lg' : ''
                         ]">
                        <div class="relative z-10">
                            <div class="text-2xl font-bold" x-text="row.weekend.toFixed(2) + '€'"></div>
                            <div class="text-xs opacity-75" x-text="'(' + ((row.weekend / basePrice - 1) * 100).toFixed(0) + '%)'"></div>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-20 transition-opacity"
                             :class="'from-' + row.color + '-400 to-' + row.color + '-600'"></div>
                    </div>
                </>
            </template>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-3 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-blue-600 dark:text-blue-400">Min. Preis</div>
                    <div class="text-lg font-bold text-blue-900 dark:text-blue-100" x-text="Math.min(...priceMatrix.map(r => r.weekday)).toFixed(2) + '€'"></div>
                </div>
                <svg class="w-8 h-8 text-blue-500 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                </svg>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-3 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-purple-600 dark:text-purple-400">Durchschnitt</div>
                    <div class="text-lg font-bold text-purple-900 dark:text-purple-100" x-text="(priceMatrix.reduce((sum, r) => sum + r.weekday + r.weekend, 0) / (priceMatrix.length * 2)).toFixed(2) + '€'"></div>
                </div>
                <svg class="w-8 h-8 text-purple-500 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-lg p-3 border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-emerald-600 dark:text-emerald-400">Max. Preis</div>
                    <div class="text-lg font-bold text-emerald-900 dark:text-emerald-100" x-text="Math.max(...priceMatrix.map(r => r.weekend)).toFixed(2) + '€'"></div>
                </div>
                <svg class="w-8 h-8 text-emerald-500 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 border border-amber-200 dark:border-amber-800">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <h5 class="text-sm font-medium text-amber-900 dark:text-amber-100">Dynamische Preisgestaltung aktiv</h5>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                    Die Preise werden automatisch basierend auf Tageszeit, Wochentag und Auslastung angepasst.
                    Klicken Sie auf eine Zelle für Details.
                </p>
            </div>
        </div>
    </div>
</div>
