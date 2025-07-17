// Livewire URL Fix
(function() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixLivewire);
    } else {
        fixLivewire();
    }
    
    function fixLivewire() {
        // Fix Livewire configuration
        if (window.Livewire) {
            window.Livewire.config = window.Livewire.config || {};
            window.Livewire.config.updateUri = 'https://api.askproai.de/livewire/update';
            console.log('Livewire update URI fixed:', window.Livewire.config.updateUri);
        }
        
        // Also fix the data attribute
        const scriptTag = document.querySelector('script[data-update-uri]');
        if (scriptTag) {
            scriptTag.setAttribute('data-update-uri', 'https://api.askproai.de/livewire/update');
        }
    }
})();