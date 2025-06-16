// Immediate Livewire fix
(function() {
    // Find the broken script tag
    const scripts = document.querySelectorAll('script[data-csrf]');
    scripts.forEach(script => {
        if (script.src === 'https://api.askproai.de' || script.src === 'https://api.askproai.de/') {
            // Load Livewire manually
            const newScript = document.createElement('script');
            newScript.src = 'https://api.askproai.de/vendor/livewire/livewire.js';
            newScript.dataset.csrf = script.dataset.csrf;
            newScript.dataset.updateUri = 'https://api.askproai.de/livewire/update';
            newScript.dataset.navigateOnce = 'true';
            
            script.parentNode.replaceChild(newScript, script);
            console.log('Livewire script fixed!');
        }
    });
})();