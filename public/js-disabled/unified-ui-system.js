/**
 * Unified UI System
 * Konsolidiert alle UI-Funktionalitäten in einem zentralen System
 * Behebt Mobile Menu, Dropdowns und Transparenz-Probleme
 */

(function() {
    'use strict';
    
    console.log('[Unified UI] Initializing...');
    
    // Alpine Store für globalen UI State
    document.addEventListener('alpine:init', () => {
        Alpine.store('ui', {
            mobileMenuOpen: false,
            activeDropdown: null,
            
            // Mobile Menu Management
            toggleMobileMenu() {
                this.mobileMenuOpen = !this.mobileMenuOpen;
                document.body.classList.toggle('fi-sidebar-open', this.mobileMenuOpen);
                
                // Update Filament's sidebar state
                if (window.Alpine && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').isOpen = this.mobileMenuOpen;
                }
                
                // Schließe alle Dropdowns wenn Mobile Menu geöffnet wird
                if (this.mobileMenuOpen) {
                    this.closeAllDropdowns();
                }
                
                console.log('[Unified UI] Mobile menu toggled:', this.mobileMenuOpen);
            },
            
            closeMobileMenu() {
                this.mobileMenuOpen = false;
                document.body.classList.remove('fi-sidebar-open');
            },
            
            // Dropdown Management
            toggleDropdown(id) {
                if (this.activeDropdown === id) {
                    this.activeDropdown = null;
                } else {
                    this.activeDropdown = id;
                }
            },
            
            closeAllDropdowns() {
                this.activeDropdown = null;
            },
            
            isDropdownOpen(id) {
                return this.activeDropdown === id;
            }
        });
    });
    
    // Global Click-Outside Handler
    document.addEventListener('click', (e) => {
        // Mobile Menu Click-Outside
        const mobileMenu = document.querySelector('.fi-sidebar');
        const menuToggle = document.querySelector('[x-on\\:click="$store.ui.toggleMobileMenu()"]');
        
        if (Alpine.store('ui').mobileMenuOpen && 
            !mobileMenu?.contains(e.target) && 
            !menuToggle?.contains(e.target)) {
            Alpine.store('ui').closeMobileMenu();
        }
        
        // Dropdown Click-Outside
        const dropdownElement = e.target.closest('[data-dropdown-id]');
        if (!dropdownElement && Alpine.store('ui').activeDropdown) {
            Alpine.store('ui').closeAllDropdowns();
        }
    });
    
    // Escape Key Handler
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Alpine.store('ui').closeMobileMenu();
            Alpine.store('ui').closeAllDropdowns();
        }
    });
    
    // Touch Support für Mobile
    let touchStartX = 0;
    let touchStartY = 0;
    
    document.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    });
    
    document.addEventListener('touchend', (e) => {
        if (!e.changedTouches.length) return;
        
        const touchEndX = e.changedTouches[0].clientX;
        const touchEndY = e.changedTouches[0].clientY;
        const diffX = touchEndX - touchStartX;
        const diffY = Math.abs(touchEndY - touchStartY);
        
        // Swipe-to-close für Mobile Menu (von rechts nach links)
        if (diffX < -50 && diffY < 100 && Alpine.store('ui').mobileMenuOpen) {
            Alpine.store('ui').closeMobileMenu();
        }
    });
    
    // Livewire Hook für DOM Updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            console.log('[Unified UI] Livewire update processed');
            // Re-initialize event handlers if needed
        });
    }
    
    // Viewport Resize Handler
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Schließe Mobile Menu auf Desktop
            if (window.innerWidth >= 1024 && Alpine.store('ui').mobileMenuOpen) {
                Alpine.store('ui').closeMobileMenu();
            }
        }, 250);
    });
    
    console.log('[Unified UI] Initialized successfully');
})();