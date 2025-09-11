/**
 * Morphing Navigation Controller
 * Alpine.js component for Stripe-style navigation
 */

document.addEventListener('alpine:init', () => {
  Alpine.data('morphingNavigation', () => ({
    // State
    isDropdownOpen: false,
    activeDropdown: null,
    isMobileOpen: false,
    mobileSection: null,
    isSearchOpen: false,
    
    // Hover intent tracking
    hoverTimeout: null,
    hoverIntentDelay: 200, // milliseconds
    
    // Dropdown positioning
    dropdownStyle: '',
    arrowStyle: '',
    contentStyle: '',
    
    // Touch gesture tracking
    touchStartX: 0,
    touchStartY: 0,
    touchEndX: 0,
    touchEndY: 0,
    swipeThreshold: 75,
    edgeSwipeZone: 20,
    
    // Initialize
    init() {
      // Setup keyboard shortcuts
      this.setupKeyboardShortcuts();
      
      // Setup touch gestures
      this.setupTouchGestures();
      
      // Setup resize observer
      this.setupResizeObserver();
      
      // Setup escape key handler
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          this.closeAll();
        }
      });
      
      // Setup click outside (handled by Alpine @click.away)
      
      // Sync with system theme
      this.watchSystemTheme();
    },
    
    // Hover handlers with intent detection
    handleHover(dropdownId) {
      // Clear any existing timeout
      if (this.hoverTimeout) {
        clearTimeout(this.hoverTimeout);
      }
      
      // Set hover intent timeout
      this.hoverTimeout = setTimeout(() => {
        if (this.activeDropdown !== dropdownId) {
          this.showDropdown(dropdownId);
        }
      }, this.hoverIntentDelay);
    },
    
    handleLeave() {
      // Clear hover timeout
      if (this.hoverTimeout) {
        clearTimeout(this.hoverTimeout);
        this.hoverTimeout = null;
      }
      
      // Close dropdown with delay
      setTimeout(() => {
        if (!this.isMouseOverDropdown()) {
          this.closeDropdown();
        }
      }, 100);
    },
    
    // Check if mouse is over dropdown
    isMouseOverDropdown() {
      const dropdown = document.querySelector('.morph-dropdown-wrapper');
      if (!dropdown) return false;
      
      const rect = dropdown.getBoundingClientRect();
      const mouseX = event.clientX;
      const mouseY = event.clientY;
      
      return mouseX >= rect.left && 
             mouseX <= rect.right && 
             mouseY >= rect.top && 
             mouseY <= rect.bottom;
    },
    
    // Toggle dropdown (for click/tap)
    toggleDropdown(dropdownId) {
      if (this.activeDropdown === dropdownId && this.isDropdownOpen) {
        this.closeDropdown();
      } else {
        this.showDropdown(dropdownId);
      }
    },
    
    // Show dropdown with morphing animation
    showDropdown(dropdownId) {
      // Get trigger element
      const trigger = document.querySelector(`[data-dropdown="${dropdownId}"]`);
      if (!trigger) return;
      
      // Calculate morphing dimensions
      const triggerRect = trigger.getBoundingClientRect();
      const navRect = document.querySelector('.morph-nav-bar').getBoundingClientRect();
      
      // Get dropdown content to measure
      const dropdownContent = document.getElementById(`${dropdownId}-dropdown`);
      if (!dropdownContent) return;
      
      // Temporarily show to measure
      dropdownContent.style.display = 'block';
      const contentRect = dropdownContent.getBoundingClientRect();
      dropdownContent.style.display = '';
      
      // Calculate optimal width (responsive)
      const viewportWidth = window.innerWidth;
      const maxWidth = Math.min(900, viewportWidth - 48);
      const contentWidth = Math.min(contentRect.width || 600, maxWidth);
      
      // Calculate position (center under trigger, but keep in viewport)
      let left = triggerRect.left + (triggerRect.width / 2) - (contentWidth / 2);
      const minLeft = 24;
      const maxLeft = viewportWidth - contentWidth - 24;
      left = Math.max(minLeft, Math.min(left, maxLeft));
      
      // Calculate arrow position
      const arrowLeft = triggerRect.left + (triggerRect.width / 2) - 6;
      
      // Set morphing styles
      this.dropdownStyle = `
        left: ${left}px;
        width: ${contentWidth}px;
        height: ${this.calculateDropdownHeight(dropdownId)}px;
        transform: scale(1);
      `;
      
      this.arrowStyle = `
        left: ${arrowLeft}px;
      `;
      
      this.contentStyle = `
        width: ${contentWidth}px;
      `;
      
      // Update state
      this.activeDropdown = dropdownId;
      this.isDropdownOpen = true;
      
      // Update ARIA
      trigger.setAttribute('aria-expanded', 'true');
      
      // Announce to screen readers
      this.announceToScreenReader(`${dropdownId} menu opened`);
    },
    
    // Calculate dropdown height based on content
    calculateDropdownHeight(dropdownId) {
      // Define heights for different dropdown types
      const heights = {
        'operations': 320,
        'management': 280,
        'system': 260,
        'default': 300
      };
      
      return heights[dropdownId] || heights.default;
    },
    
    // Close dropdown
    closeDropdown() {
      if (!this.isDropdownOpen) return;
      
      // Update ARIA
      const trigger = document.querySelector(`[data-dropdown="${this.activeDropdown}"]`);
      if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
      }
      
      // Announce to screen readers
      this.announceToScreenReader('Menu closed');
      
      // Update state with delay for animation
      setTimeout(() => {
        this.isDropdownOpen = false;
        this.activeDropdown = null;
      }, 300);
    },
    
    // Mobile menu handlers
    toggleMobileMenu() {
      this.isMobileOpen = !this.isMobileOpen;
      
      // Update hamburger ARIA
      const hamburger = document.querySelector('.morph-nav-hamburger');
      if (hamburger) {
        hamburger.setAttribute('aria-expanded', this.isMobileOpen ? 'true' : 'false');
      }
      
      // Prevent body scroll when menu is open
      if (this.isMobileOpen) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
      
      // Announce to screen readers
      this.announceToScreenReader(this.isMobileOpen ? 'Mobile menu opened' : 'Mobile menu closed');
    },
    
    closeMobileMenu() {
      this.isMobileOpen = false;
      this.mobileSection = null;
      document.body.style.overflow = '';
      
      // Update hamburger ARIA
      const hamburger = document.querySelector('.morph-nav-hamburger');
      if (hamburger) {
        hamburger.setAttribute('aria-expanded', 'false');
      }
    },
    
    toggleMobileSection(sectionId) {
      this.mobileSection = this.mobileSection === sectionId ? null : sectionId;
    },
    
    // Command palette
    openCommandPalette() {
      // This would integrate with a search modal
      console.log('Opening command palette...');
      this.isSearchOpen = true;
      
      // Dispatch custom event for other components
      window.dispatchEvent(new CustomEvent('command-palette:open'));
      
      // Announce to screen readers
      this.announceToScreenReader('Search opened. Press escape to close.');
    },
    
    // Touch gesture handlers
    setupTouchGestures() {
      // Edge swipe to open mobile menu
      document.addEventListener('touchstart', (e) => {
        this.touchStartX = e.touches[0].clientX;
        this.touchStartY = e.touches[0].clientY;
        
        // Check if starting from edge
        if (this.touchStartX <= this.edgeSwipeZone && !this.isMobileOpen) {
          e.preventDefault();
        }
      }, { passive: false });
      
      document.addEventListener('touchmove', (e) => {
        if (!this.touchStartX) return;
        
        this.touchEndX = e.touches[0].clientX;
        this.touchEndY = e.touches[0].clientY;
        
        // Calculate swipe distance
        const diffX = this.touchEndX - this.touchStartX;
        const diffY = Math.abs(this.touchEndY - this.touchStartY);
        
        // Horizontal swipe from edge
        if (this.touchStartX <= this.edgeSwipeZone && 
            diffX > this.swipeThreshold && 
            diffY < 50) {
          this.isMobileOpen = true;
        }
        
        // Swipe to close mobile menu
        if (this.isMobileOpen && diffX < -this.swipeThreshold && diffY < 50) {
          this.closeMobileMenu();
        }
      });
      
      document.addEventListener('touchend', () => {
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
      });
    },
    
    // Keyboard shortcuts
    setupKeyboardShortcuts() {
      document.addEventListener('keydown', (e) => {
        // CMD/CTRL + K for search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
          e.preventDefault();
          this.openCommandPalette();
        }
        
        // Arrow key navigation when dropdown is open
        if (this.isDropdownOpen) {
          this.handleArrowNavigation(e);
        }
      });
    },
    
    // Arrow key navigation for dropdowns
    handleArrowNavigation(e) {
      const focusableElements = document.querySelectorAll(
        '.morph-dropdown-wrapper [href], .morph-dropdown-wrapper button'
      );
      
      if (focusableElements.length === 0) return;
      
      const currentIndex = Array.from(focusableElements).indexOf(document.activeElement);
      let nextIndex = currentIndex;
      
      switch (e.key) {
        case 'ArrowDown':
          e.preventDefault();
          nextIndex = (currentIndex + 1) % focusableElements.length;
          break;
        case 'ArrowUp':
          e.preventDefault();
          nextIndex = currentIndex - 1;
          if (nextIndex < 0) nextIndex = focusableElements.length - 1;
          break;
        case 'Home':
          e.preventDefault();
          nextIndex = 0;
          break;
        case 'End':
          e.preventDefault();
          nextIndex = focusableElements.length - 1;
          break;
        default:
          return;
      }
      
      focusableElements[nextIndex].focus();
    },
    
    // Resize observer for responsive adjustments
    setupResizeObserver() {
      let resizeTimeout;
      
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          // Close dropdowns on resize to mobile
          if (window.innerWidth < 1024) {
            this.closeDropdown();
          }
          
          // Close mobile menu on resize to desktop
          if (window.innerWidth >= 1024) {
            this.closeMobileMenu();
          }
        }, 250);
      });
    },
    
    // Watch system theme changes
    watchSystemTheme() {
      if (window.matchMedia) {
        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        darkModeQuery.addEventListener('change', (e) => {
          // Theme changed, navigation will auto-update via CSS
          console.log('System theme changed:', e.matches ? 'dark' : 'light');
        });
      }
    },
    
    // Close all menus
    closeAll() {
      this.closeDropdown();
      this.closeMobileMenu();
      this.isSearchOpen = false;
    },
    
    // Screen reader announcements
    announceToScreenReader(message) {
      const announcement = document.createElement('div');
      announcement.setAttribute('role', 'status');
      announcement.setAttribute('aria-live', 'polite');
      announcement.className = 'sr-only';
      announcement.textContent = message;
      
      document.body.appendChild(announcement);
      
      setTimeout(() => {
        document.body.removeChild(announcement);
      }, 1000);
    }
  }));
});

// Export for use in other modules if needed
window.MorphingNavigation = {
  closeAll() {
    const nav = document.querySelector('[x-data*="morphingNavigation"]');
    if (nav && nav.__x) {
      nav.__x.$data.closeAll();
    }
  },
  
  openSearch() {
    const nav = document.querySelector('[x-data*="morphingNavigation"]');
    if (nav && nav.__x) {
      nav.__x.$data.openCommandPalette();
    }
  }
};