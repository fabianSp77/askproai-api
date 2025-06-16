<div wire:key="tab-info-{{ $activeTab }}">
    @php
        $currentInfo = $this->getCurrentInfo();
    @endphp
    
    <style>
        /* Verstecke das Widget bis Tabs geladen sind */
        .fi-resource-tabs + * .tab-info-container {
            display: block !important;
        }
        
        /* Stelle sicher, dass das Widget direkt nach den Tabs kommt */
        .fi-resource-tabs {
            margin-bottom: 0 !important;
        }
    </style>
    
    <!-- Container direkt unter den Tabs -->
    <div class="tab-info-container" style="margin-top: 0.5rem; margin-bottom: 1rem; position: relative;">
        
        <!-- Verbindungslinie Container -->
        <div id="tab-line-container" style="position: absolute; top: 0; left: 0; right: 0; height: 2rem;">
            <div id="tab-line" style="position: absolute; width: 2px; height: 100%; background-color: rgb(250, 204, 21); transition: left 0.3s ease;"></div>
            <div id="tab-dot" style="position: absolute; bottom: -3px; width: 8px; height: 8px; margin-left: -3px; border-radius: 50%; background-color: rgb(250, 204, 21); border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: left 0.3s ease;"></div>
        </div>
        
        <!-- Info Box -->
        <div style="padding-top: 2rem;">
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 mt-0.5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $currentInfo['title'] }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $currentInfo['description'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        function positionTabLine() {
            const activeTab = '{{ $activeTab }}';
            const tabs = document.querySelectorAll('.fi-tabs-item');
            const line = document.getElementById('tab-line');
            const dot = document.getElementById('tab-dot');
            
            if (!line || !dot || !tabs.length) return;
            
            // Finde den aktiven Tab
            let activeTabElement = null;
            tabs.forEach(tab => {
                const wireClick = tab.getAttribute('wire:click');
                if (wireClick && wireClick.includes("'" + activeTab + "'")) {
                    activeTabElement = tab;
                } else if (tab.classList.contains('fi-active')) {
                    activeTabElement = tab;
                }
            });
            
            if (activeTabElement) {
                const tabRect = activeTabElement.getBoundingClientRect();
                const containerRect = document.querySelector('.fi-tabs')?.getBoundingClientRect();
                const lineContainer = document.getElementById('tab-line-container');
                const infoContainer = document.querySelector('.tab-info-container');
                
                if (containerRect && lineContainer && infoContainer) {
                    const leftOffset = tabRect.left - containerRect.left + (tabRect.width / 2);
                    line.style.left = leftOffset + 'px';
                    dot.style.left = leftOffset + 'px';
                }
            }
        }
        
        // Initial positionieren
        setTimeout(positionTabLine, 100);
        
        // Bei Tab-Klick - Event an Livewire senden
        document.addEventListener('click', function(e) {
            const tabItem = e.target.closest('.fi-tabs-item');
            if (tabItem) {
                const wireClick = tabItem.getAttribute('wire:click');
                if (wireClick) {
                    const match = wireClick.match(/activeTab['"]\s*,\s*['"]([^'"]+)/);
                    if (match && match[1]) {
                        @this.call('updateActiveTab', match[1]);
                    }
                }
                setTimeout(positionTabLine, 300);
            }
        });
        
        // Bei Fenster-Resize
        window.addEventListener('resize', positionTabLine);
        
        // Bei Livewire Updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                setTimeout(positionTabLine, 100);
            });
        }
        
        // Überwache URL-Änderungen
        let lastTab = '{{ $activeTab }}';
        setInterval(() => {
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('activeTab') || 'today';
            if (currentTab !== lastTab) {
                lastTab = currentTab;
                @this.call('updateActiveTab', currentTab);
                setTimeout(positionTabLine, 100);
            }
        }, 500);
    })();
    </script>
</div>