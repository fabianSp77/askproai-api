// Dashboard Visual Enhancements
(function() {
    console.log('Dashboard improvements loaded');
    
    // Smooth number animations
    function animateNumber(element, start, end, duration = 1000) {
        const startTime = performance.now();
        const range = end - start;
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const value = start + (range * easeOutQuart(progress));
            element.textContent = formatNumber(Math.round(value));
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }
    
    function easeOutQuart(t) {
        return 1 - Math.pow(1 - t, 4);
    }
    
    function formatNumber(num) {
        return new Intl.NumberFormat('de-DE').format(num);
    }
    
    // Add hover effects to charts
    document.addEventListener('DOMContentLoaded', function() {
        // Animate numbers on page load
        document.querySelectorAll('.stat-value[data-value]').forEach(el => {
            const value = parseInt(el.dataset.value);
            animateNumber(el, 0, value);
        });
        
        // Add loading placeholders
        document.querySelectorAll('.chart-container:empty').forEach(el => {
            el.innerHTML = `
                <div class="empty-chart">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="9" x2="15" y2="9"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <p>Keine Daten verfügbar</p>
                </div>
            `;
        });
    });
    
    // Enhanced error handling
    window.addEventListener('error', function(e) {
        if (e.message && e.message.includes('ResizeObserver')) {
            // Ignore ResizeObserver errors from charts
            // Only prevent default for this specific error type
            e.stopPropagation();
            e.preventDefault();
            return;
        }
        // Let other errors through for proper debugging
    });
    
    // Add visual feedback for loading states
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const loaders = document.querySelectorAll('.chart-loading');
        loaders.forEach(el => el.style.display = 'block');
        
        return originalFetch.apply(this, args)
            .then(response => {
                loaders.forEach(el => el.style.display = 'none');
                return response;
            })
            .catch(error => {
                loaders.forEach(el => el.style.display = 'none');
                throw error;
            });
    };
})();