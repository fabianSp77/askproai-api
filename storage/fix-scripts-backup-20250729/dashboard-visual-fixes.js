// Dashboard Visual Enhancements
(function() {
    console.log('Dashboard visual fixes loaded');
    
    // Wait for DOM to be ready
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }
    
    ready(function() {
        // Add visual classes to dashboard elements
        const dashboard = document.querySelector('#app');
        if (dashboard) {
            dashboard.classList.add('dashboard-loaded');
        }
        
        // Fix stat card styling
        document.querySelectorAll('.ant-card').forEach(card => {
            if (!card.classList.contains('stat-card')) {
                const statistic = card.querySelector('.ant-statistic');
                if (statistic) {
                    card.classList.add('stat-card');
                }
            }
        });
        
        // Add icons to empty states
        document.querySelectorAll('.ant-empty-description').forEach(empty => {
            if (empty.textContent.includes('Keine Daten')) {
                empty.innerHTML = '<svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>' + empty.textContent;
            }
        });
        
        // Animate numbers
        function animateValue(element, start, end, duration) {
            const range = end - start;
            const startTime = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = start + (range * easeOutQuart(progress));
                
                if (element.dataset.format === 'currency') {
                    element.textContent = formatCurrency(current);
                } else if (element.dataset.format === 'percent') {
                    element.textContent = Math.round(current) + '%';
                } else {
                    element.textContent = Math.round(current).toLocaleString('de-DE');
                }
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }
        
        function easeOutQuart(t) {
            return 1 - Math.pow(1 - t, 4);
        }
        
        function formatCurrency(value) {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(value);
        }
        
        // Animate stat values
        document.querySelectorAll('.ant-statistic-content-value').forEach(el => {
            const value = parseFloat(el.textContent.replace(/[^0-9.-]/g, ''));
            if (!isNaN(value)) {
                animateValue(el, 0, value, 1000);
            }
        });
        
        // Add hover effects
        document.querySelectorAll('.ant-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Fix chart responsiveness
        window.addEventListener('resize', function() {
            // Trigger recharts resize
            window.dispatchEvent(new Event('recharts-resize'));
        });
        
        // Add loading shimmer effect
        document.querySelectorAll('.loading').forEach(el => {
            el.classList.add('loading-shimmer');
        });
    });
    
    // Override console errors for ResizeObserver
    const resizeObserverErr = window.console.error;
    window.console.error = function(...args) {
        if (args[0] && typeof args[0] === 'string' && args[0].includes('ResizeObserver')) {
            return;
        }
        resizeObserverErr.apply(console, args);
    };
})();