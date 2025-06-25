// Force apply modern styles to Retell Ultimate Dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the Retell Ultimate Dashboard page
    const dashboard = document.querySelector('.retell-ultimate-dashboard');
    if (!dashboard) return;
    
    // Force apply glassmorphism to all cards
    const cards = dashboard.querySelectorAll('.bg-white.rounded-xl.shadow-sm');
    cards.forEach(card => {
        // Add glass-card class if not already present
        if (!card.classList.contains('glass-card')) {
            card.classList.add('glass-card');
        }
    });
    
    // Force apply modern button styles
    const buttons = dashboard.querySelectorAll('button');
    buttons.forEach(button => {
        // Skip if it's already styled or is a specific type
        if (!button.classList.contains('btn-modern') && 
            !button.classList.contains('fi-btn') &&
            button.textContent.trim() !== '') {
            button.classList.add('btn-modern');
        }
    });
    
    // Apply function card styles
    const functionCards = dashboard.querySelectorAll('[wire\\:click*="selectAgent"]');
    functionCards.forEach(card => {
        if (!card.classList.contains('function-card')) {
            card.classList.add('function-card');
        }
    });
    
    // Force re-apply styles on Livewire updates
    if (window.Livewire) {
        window.Livewire.hook('message.processed', (message, component) => {
            setTimeout(() => {
                // Re-apply styles after Livewire update
                const updatedCards = dashboard.querySelectorAll('.bg-white.rounded-xl.shadow-sm:not(.glass-card)');
                updatedCards.forEach(card => {
                    card.classList.add('glass-card');
                });
            }, 100);
        });
    }
    
    console.log('Retell Ultimate Dashboard modern styles applied');
});