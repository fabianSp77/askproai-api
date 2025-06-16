<x-filament-widgets::widget>
    @php
        // Get the current active tab from URL parameter
        $activeTab = request()->query('activeTab', 'today');
        $tabs = $this->getCachedTabDescriptions();
    @endphp
    
    <div class="tab-descriptions-widget">
        <div class="tab-descriptions-container">
            @foreach($tabs as $key => $tab)
                <div class="tab-description {{ $activeTab === $key ? 'active' : '' }}" data-tab="{{ $key }}">
                    <h3>{{ $tab['title'] }}</h3>
                    <p>{{ $tab['hint'] }}</p>
                    @if($activeTab === $key)
                        <div class="tab-description-connector"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Listen for URL changes to update active tab
            const observer = new MutationObserver(function() {
                const urlParams = new URLSearchParams(window.location.search);
                const activeTab = urlParams.get('activeTab') || 'today';
                
                document.querySelectorAll('.tab-description').forEach(desc => {
                    if (desc.dataset.tab === activeTab) {
                        desc.classList.add('active');
                        // Add connector if it doesn't exist
                        if (!desc.querySelector('.tab-description-connector')) {
                            const connector = document.createElement('div');
                            connector.className = 'tab-description-connector';
                            desc.appendChild(connector);
                        }
                    } else {
                        desc.classList.remove('active');
                        // Remove connector
                        const connector = desc.querySelector('.tab-description-connector');
                        if (connector) {
                            connector.remove();
                        }
                    }
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
</x-filament-widgets::widget>