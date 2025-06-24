// Company Integration Portal - Clean JavaScript
// Only essential functionality, no DOM manipulation that breaks Filament

document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the Company Integration Portal page
    if (!document.querySelector('.fi-company-integration-portal')) {
        return;
    }

    // Log successful initialization
    console.log('Company Integration Portal initialized');

    // Add keyboard navigation for company cards
    const companyButtons = document.querySelectorAll('button[wire\\:click*="selectCompany"]');
    companyButtons.forEach((button, index) => {
        button.setAttribute('tabindex', '0');
        
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Monitor Livewire events for better UX
    if (window.Livewire) {
        Livewire.on('company-selected', (companyId) => {
            console.log('Company selected:', companyId);
        });

        Livewire.on('test-completed', (result) => {
            console.log('Test completed:', result);
        });
    }

    // Handle connection test results with notifications
    window.addEventListener('test-result', function(event) {
        const { service, success, message } = event.detail;
        
        // Let Filament handle the notifications
        if (window.FilamentNotifications) {
            window.FilamentNotifications.notify(
                success ? 'success' : 'danger',
                message
            );
        }
    });

    // Ensure modals work properly
    document.addEventListener('click', function(e) {
        // Don't interfere with Filament's modal handling
        if (e.target.closest('.fi-modal')) {
            return;
        }
    });

    // Add accessibility improvements
    const sections = document.querySelectorAll('.fi-section');
    sections.forEach(section => {
        const heading = section.querySelector('.fi-section-heading');
        if (heading && !heading.id) {
            const id = 'section-' + Math.random().toString(36).substr(2, 9);
            heading.id = id;
            section.setAttribute('aria-labelledby', id);
        }
    });
});

// Export for potential use in other scripts
window.CompanyIntegrationPortal = {
    initialized: true,
    version: '2.0.0'
};