<x-filament-widgets::widget class="tab-description-widget">
    @php
        try {
            $tabInfo = $this->getTabInfo();
            $color = $tabInfo['color'] ?? 'gray';
        } catch (\Exception $e) {
            $tabInfo = [
                'title' => 'Anrufe',
                'description' => 'Wählen Sie einen Tab aus, um die Anrufe zu filtern.',
                'icon' => 'heroicon-o-phone',
                'color' => 'gray'
            ];
            $color = 'gray';
        }
    @endphp
    
    <div class="fi-wi-stats-overview-card relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="flex h-12 w-12 items-center justify-center rounded-full 
                    @if($color === 'primary') bg-primary-50 dark:bg-primary-950/50
                    @elseif($color === 'success') bg-success-50 dark:bg-success-950/50
                    @elseif($color === 'danger') bg-danger-50 dark:bg-danger-950/50
                    @elseif($color === 'warning') bg-warning-50 dark:bg-warning-950/50
                    @else bg-gray-50 dark:bg-gray-950/50
                    @endif">
                    <x-dynamic-component 
                        :component="$tabInfo['icon']" 
                        class="h-6 w-6 
                            @if($color === 'primary') text-primary-600 dark:text-primary-400
                            @elseif($color === 'success') text-success-600 dark:text-success-400
                            @elseif($color === 'danger') text-danger-600 dark:text-danger-400
                            @elseif($color === 'warning') text-warning-600 dark:text-warning-400
                            @else text-gray-600 dark:text-gray-400
                            @endif"
                    />
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $tabInfo['title'] }}
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $tabInfo['description'] }}
                </p>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

@push('scripts')
<script>
    // Verbessere die Spaltenauswahl
    document.addEventListener('DOMContentLoaded', function() {
        // Tab-Wechsel überwachen
        const watchTabChanges = () => {
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('activeTab') || 'today';
            
            // Dispatch tab-changed event wenn sich der Tab ändert
            const checkTab = setInterval(() => {
                const newParams = new URLSearchParams(window.location.search);
                const newTab = newParams.get('activeTab') || 'today';
                
                if (newTab !== currentTab) {
                    // Tab hat sich geändert - Widget wird durch Livewire automatisch aktualisiert
                    clearInterval(checkTab);
                    window.location.reload();
                }
            }, 100);
            
            // Stop checking after 5 seconds
            setTimeout(() => clearInterval(checkTab), 5000);
        };
        
        // Bei jedem Klick auf einen Tab
        document.addEventListener('click', (e) => {
            if (e.target.closest('[role="tab"]')) {
                setTimeout(watchTabChanges, 100);
            }
        });
        // Warte auf das Dropdown
        const improveColumnToggle = () => {
            const toggleButtons = document.querySelectorAll('[x-on\\:click*="columnsOpen"]');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    setTimeout(() => {
                        const panel = document.querySelector('.fi-ta-col-toggle .fi-dropdown-panel');
                        if (panel && !panel.dataset.enhanced) {
                            panel.dataset.enhanced = 'true';
                            
                            // Setze max-height und scrollbar
                            panel.style.maxHeight = '70vh';
                            panel.style.overflowY = 'auto';
                            
                            // Auf Mobile zentrieren
                            if (window.innerWidth < 640) {
                                panel.style.width = '90vw';
                                panel.style.left = '50%';
                                panel.style.transform = 'translateX(-50%)';
                            }
                        }
                    }, 100);
                });
            });
        };
        
        // Initial und bei Livewire Updates
        improveColumnToggle();
        Livewire.hook('message.processed', improveColumnToggle);
    });
</script>
@endpush