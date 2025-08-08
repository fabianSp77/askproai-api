// Analytics Livewire Integration
// This file ensures charts are created when Livewire updates the page

if (window.Livewire) {
    console.log('🔌 Analytics Livewire Integration loaded');
    
    // Function to check and create charts
    function checkAndCreateCharts() {
        // Check if we have chart data
        if (window.analyticsChartData && Object.keys(window.analyticsChartData).length > 0) {
            console.log('📊 Chart data detected, creating charts...');
            
            // Wait for chart libraries and then create
            if (typeof Chart !== 'undefined' && typeof ApexCharts !== 'undefined') {
                if (window.analyticsChartDebug && window.analyticsChartDebug.create) {
                    window.analyticsChartDebug.create();
                }
            } else {
                console.log('⏳ Waiting for chart libraries...');
                setTimeout(checkAndCreateCharts, 500);
            }
        }
    }
    
    // Listen for Livewire updates
    Livewire.hook('message.processed', (message, component) => {
        if (component && component.fingerprint && 
            component.fingerprint.name && 
            component.fingerprint.name.includes('event-analytics-dashboard')) {
            
            console.log('📨 Analytics dashboard processed');
            
            // Check for inline scripts and evaluate them
            setTimeout(() => {
                const scripts = document.querySelectorAll('script:not([src])');
                scripts.forEach(script => {
                    if (script.textContent.includes('window.analyticsChartData')) {
                        console.log('📜 Found and evaluating data script');
                        try {
                            eval(script.textContent);
                        } catch (e) {
                            console.error('Error evaluating script:', e);
                        }
                    }
                });
                
                // Now check for charts
                checkAndCreateCharts();
            }, 100);
        }
    });
    
    // Also check on initial load
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(checkAndCreateCharts, 1000);
    });
}