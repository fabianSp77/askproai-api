{{-- Mobile Sidebar Text Fix - Inline Styles --}}
<style>
/* Force mobile sidebar text visibility */
@media (max-width: 1024px) {
    body.fi-sidebar-open .fi-sidebar span.fi-sidebar-item-label {
        display: inline-block !important;
        opacity: 1 !important;
        visibility: visible !important;
        width: auto !important;
        position: static !important;
        clip: auto !important;
        overflow: visible !important;
    }
    
    body.fi-sidebar-open .fi-sidebar [x-show="$store.sidebar.isOpen"] {
        display: initial !important;
        opacity: 1 !important;
    }
    
    body.fi-sidebar-open .fi-sidebar [x-transition\:enter] {
        transition: none !important;
        opacity: 1 !important;
    }
}
</style>

<script>
// Force mobile sidebar text visibility
document.addEventListener('alpine:initialized', function() {
    // Override Alpine behavior for mobile
    if (window.innerWidth < 1024 && window.Alpine && window.Alpine.store('sidebar')) {
        const store = window.Alpine.store('sidebar');
        const originalToggle = store.toggle;
        
        store.toggle = function() {
            originalToggle.call(this);
            
            // Force text visibility after toggle
            if (document.body.classList.contains('fi-sidebar-open')) {
                setTimeout(() => {
                    // Force all x-show elements to display
                    const xShowElements = document.querySelectorAll('.fi-sidebar [x-show="$store.sidebar.isOpen"]');
                    xShowElements.forEach(el => {
                        el.style.display = '';
                        el.style.opacity = '1';
                        el.style.visibility = 'visible';
                    });
                    
                    // Force labels specifically
                    const labels = document.querySelectorAll('.fi-sidebar-item-label');
                    labels.forEach(label => {
                        label.style.display = 'inline-block';
                        label.style.opacity = '1';
                        label.style.visibility = 'visible';
                    });
                }, 50);
            }
        };
    }
});

// Also watch for body class changes
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const hasSidebarOpen = document.body.classList.contains('fi-sidebar-open');
                const isMobile = window.innerWidth < 1024;
                
                if (hasSidebarOpen && isMobile) {
                    // Force text visibility
                    setTimeout(() => {
                        document.querySelectorAll('.fi-sidebar [x-show="$store.sidebar.isOpen"]').forEach(el => {
                            el.style.display = '';
                            el.style.opacity = '1';
                        });
                        
                        document.querySelectorAll('.fi-sidebar-item-label, .fi-sidebar-group-label').forEach(label => {
                            label.style.display = 'inline-block';
                            label.style.opacity = '1';
                            label.style.visibility = 'visible';
                        });
                    }, 100);
                }
            }
        });
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
});
</script>