// Filament Navigation Enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced Mobile Sidebar Toggle
    const initMobileSidebar = () => {
        const sidebar = document.querySelector('.fi-sidebar');
        const body = document.body;
        
        if (!sidebar) return;
        
        // Create overlay element
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay fixed inset-0 bg-black/50 z-30 lg:hidden opacity-0 pointer-events-none transition-opacity duration-300';
        body.appendChild(overlay);
        
        // Toggle function
        window.toggleMobileSidebar = () => {
            const isOpen = sidebar.classList.contains('translate-x-0');
            
            if (isOpen) {
                // Close
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-100', 'pointer-events-auto');
                overlay.classList.add('opacity-0', 'pointer-events-none');
                body.style.overflow = '';
            } else {
                // Open
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                overlay.classList.add('opacity-100', 'pointer-events-auto');
                body.style.overflow = 'hidden';
            }
        };
        
        // Close on overlay click
        overlay.addEventListener('click', window.toggleMobileSidebar);
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('translate-x-0')) {
                window.toggleMobileSidebar();
            }
        });
        
        // Add mobile styles to sidebar
        if (window.innerWidth < 1024) {
            sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-40', 'w-64', 'transform', '-translate-x-full', 'transition-transform', 'duration-300', 'ease-in-out');
        }
    };
    
    // Enhanced Branch Switcher Search
    const enhanceBranchSearch = () => {
        const searchInputs = document.querySelectorAll('[x-model="search"]');
        
        searchInputs.forEach(input => {
            // Add clear button
            const clearButton = document.createElement('button');
            clearButton.innerHTML = '×';
            clearButton.className = 'absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg leading-none';
            clearButton.style.display = 'none';
            
            input.parentElement.style.position = 'relative';
            input.parentElement.appendChild(clearButton);
            
            // Show/hide clear button
            input.addEventListener('input', () => {
                clearButton.style.display = input.value ? 'block' : 'none';
            });
            
            // Clear input
            clearButton.addEventListener('click', () => {
                input.value = '';
                input.dispatchEvent(new Event('input'));
                input.focus();
            });
        });
    };
    
    // Smooth scroll to active navigation item
    const scrollToActiveItem = () => {
        const activeItem = document.querySelector('.fi-sidebar-item-active');
        const sidebar = document.querySelector('.fi-sidebar-nav');
        
        if (activeItem && sidebar) {
            const itemRect = activeItem.getBoundingClientRect();
            const sidebarRect = sidebar.getBoundingClientRect();
            
            if (itemRect.top < sidebarRect.top || itemRect.bottom > sidebarRect.bottom) {
                activeItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    };
    
    // Keyboard navigation for dropdown
    const enhanceKeyboardNavigation = () => {
        document.addEventListener('keydown', (e) => {
            const dropdown = document.querySelector('[x-show="open"]:not(.hidden)');
            if (!dropdown) return;
            
            const items = dropdown.querySelectorAll('a[role="menuitem"]');
            const currentIndex = Array.from(items).findIndex(item => item === document.activeElement);
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = currentIndex + 1 < items.length ? currentIndex + 1 : 0;
                    items[nextIndex]?.focus();
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = currentIndex - 1 >= 0 ? currentIndex - 1 : items.length - 1;
                    items[prevIndex]?.focus();
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    items[0]?.focus();
                    break;
                    
                case 'End':
                    e.preventDefault();
                    items[items.length - 1]?.focus();
                    break;
            }
        });
    };
    
    // Responsive sidebar behavior
    const handleResponsiveSidebar = () => {
        let lastWidth = window.innerWidth;
        
        window.addEventListener('resize', () => {
            const currentWidth = window.innerWidth;
            const sidebar = document.querySelector('.fi-sidebar');
            
            if (!sidebar) return;
            
            // Crossing breakpoint from mobile to desktop
            if (lastWidth < 1024 && currentWidth >= 1024) {
                sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-40', 'w-64', 'transform', '-translate-x-full', 'transition-transform', 'duration-300', 'ease-in-out');
                document.body.style.overflow = '';
            }
            // Crossing breakpoint from desktop to mobile
            else if (lastWidth >= 1024 && currentWidth < 1024) {
                sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-40', 'w-64', 'transform', '-translate-x-full', 'transition-transform', 'duration-300', 'ease-in-out');
            }
            
            lastWidth = currentWidth;
        });
    };
    
    // Touch gestures for mobile
    const addTouchGestures = () => {
        const sidebar = document.querySelector('.fi-sidebar');
        if (!sidebar || window.innerWidth >= 1024) return;
        
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        const handleSwipe = () => {
            const swipeDistance = touchEndX - touchStartX;
            const threshold = 50;
            
            // Swipe right to open
            if (swipeDistance > threshold && touchStartX < 20 && !sidebar.classList.contains('translate-x-0')) {
                window.toggleMobileSidebar?.();
            }
            // Swipe left to close
            else if (swipeDistance < -threshold && sidebar.classList.contains('translate-x-0')) {
                window.toggleMobileSidebar?.();
            }
        };
    };
    
    // Initialize all enhancements
    initMobileSidebar();
    enhanceBranchSearch();
    scrollToActiveItem();
    enhanceKeyboardNavigation();
    handleResponsiveSidebar();
    addTouchGestures();
    
    // Re-initialize after Livewire updates
    if (window.Livewire) {
        window.Livewire.hook('message.processed', () => {
            enhanceBranchSearch();
            scrollToActiveItem();
        });
    }
});