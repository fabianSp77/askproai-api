<!-- Global Help Widget -->
<div id="global-help-widget" class="fixed bottom-6 right-6 z-50">
    <!-- Help Button -->
    <button id="help-widget-button" 
            class="bg-blue-600 text-white rounded-full w-14 h-14 shadow-lg hover:bg-blue-700 transition-all hover:scale-110 flex items-center justify-center group relative"
            aria-label="Hilfe öffnen">
        <svg class="w-6 h-6 group-hover:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <svg class="w-6 h-6 hidden group-hover:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        
        <!-- Pulse Animation for New Users -->
        <span class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full animate-ping"></span>
        <span class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full"></span>
    </button>
    
    <!-- Help Panel -->
    <div id="help-widget-panel" class="hidden absolute bottom-16 right-0 bg-white rounded-lg shadow-xl w-96 max-h-[600px] border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-lg">Wie können wir helfen?</h4>
                <button id="help-widget-close" class="text-white/80 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Search -->
        <div class="p-4 border-b border-gray-200">
            <form action="{{ route('help.search') }}" method="GET" target="_blank" class="relative">
                <input type="text" 
                       name="q" 
                       placeholder="Suche nach Hilfe..." 
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                       id="help-widget-search">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </form>
        </div>
        
        <!-- Quick Links -->
        <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
            <h5 class="font-semibold text-gray-700 mb-2">Beliebte Themen</h5>
            
            <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'first-call']) }}" 
               target="_blank"
               class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-800">Ersten Termin buchen</p>
                    <p class="text-sm text-gray-600">So buchen Sie Termine per Telefon</p>
                </div>
            </a>
            
            <a href="{{ route('help.article', ['category' => 'appointments', 'topic' => 'view-appointments']) }}" 
               target="_blank"
               class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-800">Termine verwalten</p>
                    <p class="text-sm text-gray-600">Termine anzeigen und bearbeiten</p>
                </div>
            </a>
            
            <a href="{{ route('help.article', ['category' => 'account', 'topic' => 'password-change']) }}" 
               target="_blank"
               class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-800">Passwort ändern</p>
                    <p class="text-sm text-gray-600">Sicheres Passwort festlegen</p>
                </div>
            </a>
            
            <a href="{{ route('help.article', ['category' => 'troubleshooting', 'topic' => 'common-issues']) }}" 
               target="_blank"
               class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-800">Probleme lösen</p>
                    <p class="text-sm text-gray-600">Häufige Probleme beheben</p>
                </div>
            </a>
        </div>
        
        <!-- Footer -->
        <div class="border-t border-gray-200 p-4 bg-gray-50">
            <a href="{{ route('help.index') }}" 
               target="_blank"
               class="block text-center text-blue-600 hover:text-blue-700 font-medium">
                Alle Hilfethemen anzeigen →
            </a>
        </div>
    </div>
</div>

<style>
@keyframes slideIn {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

#help-widget-panel {
    animation: slideIn 0.3s ease-out;
}

@media (max-width: 640px) {
    #help-widget-panel {
        width: calc(100vw - 2rem);
        right: 1rem;
        left: 1rem;
        bottom: 5rem;
    }
    
    #global-help-widget {
        bottom: 1rem;
        right: 1rem;
    }
}
</style>

<script>
(function() {
    const widget = document.getElementById('global-help-widget');
    const button = document.getElementById('help-widget-button');
    const panel = document.getElementById('help-widget-panel');
    const closeBtn = document.getElementById('help-widget-close');
    const searchInput = document.getElementById('help-widget-search');
    
    let isOpen = false;
    
    // Toggle panel
    function togglePanel() {
        isOpen = !isOpen;
        if (isOpen) {
            panel.classList.remove('hidden');
            searchInput.focus();
            // Remove pulse animation after first open
            const pulseElements = button.querySelectorAll('.animate-ping, .bg-red-500');
            pulseElements.forEach(el => el.remove());
            // Save state
            localStorage.setItem('helpWidgetOpened', 'true');
        } else {
            panel.classList.add('hidden');
        }
    }
    
    // Event listeners
    button.addEventListener('click', togglePanel);
    closeBtn.addEventListener('click', togglePanel);
    
    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!widget.contains(e.target) && isOpen) {
            togglePanel();
        }
    });
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) {
            togglePanel();
        }
    });
    
    // Remove pulse if widget was already opened
    if (localStorage.getItem('helpWidgetOpened') === 'true') {
        const pulseElements = button.querySelectorAll('.animate-ping, .bg-red-500');
        pulseElements.forEach(el => el.remove());
    }
    
    // Search on enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = this.value.trim();
            if (query) {
                window.open('{{ route('help.search') }}?q=' + encodeURIComponent(query), '_blank');
                this.value = '';
            }
        }
    });
})();
</script>