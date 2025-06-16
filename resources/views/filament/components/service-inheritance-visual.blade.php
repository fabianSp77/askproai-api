<div class="service-inheritance-visual" x-data="serviceInheritanceVisual()">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            Service-Vererbungshierarchie
        </h3>
        
        <div class="space-y-4">
            <!-- Unternehmensebene -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-700 dark:text-gray-300 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100-4h-.09A1.65 1.65 0 018 4.65a1.65 1.65 0 00-.7-1.32l-.3-.23V3a1 1 0 00-2 0v.09A1.65 1.65 0 005 4.65 1.65 1.65 0 005.7 5.98l.3.23v.09a2 2 0 00-2 2v7a2 2 0 002 2h8a2 2 0 002-2v-7a2 2 0 00-2-2V6z" clip-rule="evenodd"></path>
                        </svg>
                        Unternehmens-Services
                    </h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="companyServices.length"></span> Services definiert
                    </span>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <template x-for="service in companyServices" :key="service.id">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded px-3 py-2 text-sm">
                            <div class="font-medium text-blue-900 dark:text-blue-200" x-text="service.name"></div>
                            <div class="text-xs text-blue-700 dark:text-blue-300">
                                <span x-text="service.duration_minutes"></span> Min • €<span x-text="service.price"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Vererbungspfeil -->
            <div class="flex justify-center">
                <svg class="w-6 h-8 text-gray-400 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a1 1 0 01-.707-.293l-7-7a1 1 0 111.414-1.414L10 15.586l6.293-6.293a1 1 0 111.414 1.414l-7 7A1 1 0 0110 18z" clip-rule="evenodd"></path>
                </svg>
            </div>
            
            <!-- Filialebene -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border-2 border-indigo-200 dark:border-indigo-700">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-700 dark:text-gray-300 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm6 6H7v2h6v-2z" clip-rule="evenodd"></path>
                        </svg>
                        Filial-Anpassungen
                    </h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="overrides.length"></span> Überschreibungen
                    </span>
                </div>
                
                <div class="space-y-2">
                    <template x-for="override in overrides" :key="override.id">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded px-3 py-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-indigo-900 dark:text-indigo-200" x-text="override.service_name"></span>
                                <div class="flex items-center space-x-2">
                                    <template x-if="override.duration_changed">
                                        <span class="text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200 px-2 py-1 rounded">
                                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span x-text="override.duration_minutes"></span> Min
                                        </span>
                                    </template>
                                    <template x-if="override.price_changed">
                                        <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200 px-2 py-1 rounded">
                                            €<span x-text="override.price"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="overrides.length === 0">
                        <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-4">
                            Keine filialspezifischen Anpassungen - alle Unternehmens-Services werden 1:1 übernommen
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Legende -->
            <div class="mt-4 flex items-center justify-center space-x-4 text-xs text-gray-600 dark:text-gray-400">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-100 dark:bg-blue-900/30 rounded mr-1"></div>
                    <span>Unternehmens-Standard</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-indigo-100 dark:bg-indigo-900/30 rounded mr-1"></div>
                    <span>Filial-Anpassung</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function serviceInheritanceVisual() {
            return {
                companyServices: @json($getRecord()?->company?->masterServices ?? []),
                overrides: @json($getRecord()?->serviceOverrides ?? []),
                
                init() {
                    // Markiere Änderungen
                    this.overrides = this.overrides.map(override => {
                        const masterService = this.companyServices.find(s => s.id === override.master_service_id);
                        if (masterService) {
                            override.service_name = masterService.name;
                            override.duration_changed = override.duration_minutes && override.duration_minutes !== masterService.duration_minutes;
                            override.price_changed = override.price && parseFloat(override.price) !== parseFloat(masterService.price);
                        }
                        return override;
                    });
                }
            }
        }
    </script>
</div>
