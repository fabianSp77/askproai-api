/**
 * Portal UI Enhancements
 * Modern UX improvements for the Business Portal
 */
(function() {
    'use strict';
    
    // === Smooth Page Transitions ===
    document.addEventListener('DOMContentLoaded', function() {
        // Add loading state to links
        const links = document.querySelectorAll('a[href^="/business"]');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                // Skip if cmd/ctrl click (new tab)
                if (e.metaKey || e.ctrlKey) return;
                
                // Skip if external link
                if (link.host !== window.location.host) return;
                
                // Add loading class to body
                document.body.classList.add('page-loading');
                
                // Show loading indicator in header
                const header = document.querySelector('header');
                if (header) {
                    const loader = document.createElement('div');
                    loader.className = 'page-loader';
                    loader.innerHTML = '<div class="loader-bar"></div>';
                    header.appendChild(loader);
                }
            });
        });
    });
    
    // === Auto-save Form Data ===
    const forms = document.querySelectorAll('form[data-auto-save]');
    forms.forEach(form => {
        let saveTimeout;
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                
                // Show saving indicator
                const indicator = form.querySelector('.save-indicator') || createSaveIndicator(form);
                indicator.textContent = 'Speichert...';
                indicator.className = 'save-indicator saving';
                
                // Save after 1 second of no typing
                saveTimeout = setTimeout(() => {
                    // Save to localStorage
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData);
                    localStorage.setItem(`form_${form.id}`, JSON.stringify(data));
                    
                    // Update indicator
                    indicator.textContent = 'Gespeichert';
                    indicator.className = 'save-indicator saved';
                    
                    setTimeout(() => {
                        indicator.classList.add('fade-out');
                    }, 2000);
                }, 1000);
            });
        });
    });
    
    function createSaveIndicator(form) {
        const indicator = document.createElement('span');
        indicator.className = 'save-indicator';
        form.appendChild(indicator);
        return indicator;
    }
    
    // === Keyboard Shortcuts ===
    document.addEventListener('keydown', function(e) {
        // Cmd/Ctrl + K for search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            focusSearch();
        }
        
        // Escape to close modals/dropdowns
        if (e.key === 'Escape') {
            closeAllDropdowns();
            closeModals();
        }
    });
    
    function focusSearch() {
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="Suche"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    function closeAllDropdowns() {
        // Close Alpine.js dropdowns
        document.querySelectorAll('[x-data*="open"]').forEach(el => {
            if (el.__x) {
                el.__x.$data.open = false;
            }
        });
    }
    
    function closeModals() {
        // Close any open modals
        document.querySelectorAll('.modal.open').forEach(modal => {
            modal.classList.remove('open');
        });
    }
    
    // === Table Row Click ===
    const tableRows = document.querySelectorAll('tr[data-href]');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            // Skip if clicking on a button or link
            if (e.target.closest('a, button')) return;
            
            window.location.href = row.dataset.href;
        });
    });
    
    // === Tooltip Enhancement ===
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(el => {
        const title = el.getAttribute('title');
        el.removeAttribute('title');
        el.setAttribute('data-tooltip', title);
        
        // Create modern tooltip on hover
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
    
    function showTooltip(e) {
        const text = e.target.getAttribute('data-tooltip');
        if (!text) return;
        
        const tooltip = document.createElement('div');
        tooltip.className = 'modern-tooltip';
        tooltip.textContent = text;
        document.body.appendChild(tooltip);
        
        // Position tooltip
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
        
        e.target._tooltip = tooltip;
    }
    
    function hideTooltip(e) {
        if (e.target._tooltip) {
            e.target._tooltip.remove();
            delete e.target._tooltip;
        }
    }
    
    console.log('[Portal Enhancements] ✨ Modern UX features loaded');
})();