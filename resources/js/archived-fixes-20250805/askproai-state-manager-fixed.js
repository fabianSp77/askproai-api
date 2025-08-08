/**
 * AskProAI State Manager
 * 
 * Centralized state management for consistent UI behavior
 * across the entire portal. Works with Alpine.js v3 and
 * Livewire v3 for reactive state updates.
 */

document.addEventListener('alpine:init', () => {
  // Global UI State Store
  Alpine.store('uiState', {
    // Track open dropdowns
    dropdowns: {},
    
    // Track active elements (navigation, tabs, etc.)
    activeElements: new Set(),
    
    // Track form states
    formStates: {},
    
    // Track loading states
    loadingStates: {},
    
    // Store cleanup functions
    cleanupFunctions: new WeakMap(),
    
    /**
     * Dropdown Management
     */
    toggleDropdown(id) {
      // Close all other dropdowns first
      Object.keys(this.dropdowns).forEach(key => {
        if (key !== id) {
          this.dropdowns[key] = false;
        }
      });
      
      // Toggle the requested dropdown
      this.dropdowns[id] = !this.dropdowns[id];
      
      // Emit event for other components to listen
      window.dispatchEvent(new CustomEvent('dropdown-toggled', {
        detail: { id, open: this.dropdowns[id] }
      }));
    },
    
    closeDropdown(id) {
      this.dropdowns[id] = false;
    },
    
    closeAllDropdowns() {
      Object.keys(this.dropdowns).forEach(key => {
        this.dropdowns[key] = false;
      });
    },
    
    isDropdownOpen(id) {
      return this.dropdowns[id] || false;
    },
    
    /**
     * Active Element Management
     */
    setActive(element, state = true) {
      if (state) {
        this.activeElements.add(element);
      } else {
        this.activeElements.delete(element);
      }
      
      // Emit event
      window.dispatchEvent(new CustomEvent('element-active-changed', {
        detail: { element, active: state }
      }));
    },
    
    isActive(element) {
      return this.activeElements.has(element);
    },
    
    toggleActive(element) {
      const newState = !this.isActive(element);
      this.setActive(element, newState);
      return newState;
    },
    
    /**
     * Form State Management
     */
    setFormState(formId, field, value) {
      if (!this.formStates[formId]) {
        this.formStates[formId] = {};
      }
      this.formStates[formId][field] = value;
    },
    
    getFormState(formId, field = null) {
      if (!this.formStates[formId]) return null;
      return field ? this.formStates[formId][field] : this.formStates[formId];
    },
    
    clearFormState(formId) {
      delete this.formStates[formId];
    },
    
    /**
     * Loading State Management
     */
    setLoading(key, state = true) {
      this.loadingStates[key] = state;
      
      // Update body class for global loading state
      if (state) {
        document.body.classList.add('is-loading');
      } else {
        // Check if any other loading states are active
        const hasActiveLoading = Object.values(this.loadingStates).some(s => s);
        if (!hasActiveLoading) {
          document.body.classList.remove('is-loading');
        }
      }
    },
    
    isLoading(key) {
      return this.loadingStates[key] || false;
    },
    
    /**
     * Persist state to localStorage
     */
    persist() {
      try {
        const state = {
          activeElements: Array.from(this.activeElements),
          formStates: this.formStates
        };
        localStorage.setItem('askproai_ui_state', JSON.stringify(state));
      } catch (e) {
        console.warn('Failed to persist UI state:', e);
      }
    },
    
    /**
     * Restore state from localStorage
     */
    restore() {
      try {
        const saved = localStorage.getItem('askproai_ui_state');
        if (saved) {
          const state = JSON.parse(saved);
          this.activeElements = new Set(state.activeElements || []);
          this.formStates = state.formStates || {};
        }
      } catch (e) {
        console.error('Failed to restore UI state:', e);
      }
    },
    
    /**
     * Register cleanup function for a component
     */
    registerCleanup(element, cleanupFn) {
      const existingCleanups = this.cleanupFunctions.get(element) || [];
      existingCleanups.push(cleanupFn);
      this.cleanupFunctions.set(element, existingCleanups);
    },
    
    /**
     * Run cleanup functions for a component
     */
    cleanup(element) {
      const cleanups = this.cleanupFunctions.get(element);
      if (cleanups) {
        cleanups.forEach(fn => fn());
        this.cleanupFunctions.delete(element);
      }
    }
  });
  
  // Restore state on init
  Alpine.store('uiState').restore();
  
  // Save state before page unload
  window.addEventListener('beforeunload', () => {
    Alpine.store('uiState').persist();
  });
  
  /**
   * Alpine Magic Helpers
   */
  
  // Click outside directive
  Alpine.magic('clickOutside', (el) => {
    return (callback) => {
      const handler = (e) => {
        if (!el.contains(e.target) && el !== e.target) {
          callback(e);
        }
      };
      
      // Use capture phase to catch events before they bubble
      document.addEventListener('click', handler, true);
      
      // Cleanup function
      return () => {
        document.removeEventListener('click', handler, true);
      };
    };
  });
  
  // Focus trap magic
  Alpine.magic('trapFocus', (el) => {
    const focusableElements = el.querySelectorAll(
      'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length === 0) {
      return () => {}; // Return empty cleanup if no focusable elements
    }
    
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];
    
    const handler = (e) => {
      if (e.key === 'Tab' && firstFocusable && lastFocusable) {
        if (e.shiftKey) {
          if (document.activeElement === firstFocusable) {
            e.preventDefault();
            lastFocusable.focus();
          }
        } else {
          if (document.activeElement === lastFocusable) {
            e.preventDefault();
            firstFocusable.focus();
          }
        }
      }
    };
    
    el.addEventListener('keydown', handler);
    if (firstFocusable) {
      firstFocusable.focus();
    }
    
    return () => {
      el.removeEventListener('keydown', handler);
    };
  });
  
  /**
   * Alpine Components
   */
  
  // Dropdown component
  Alpine.data('dropdown', (id = null) => ({
    id: id || `dropdown-${Math.random().toString(36).substr(2, 9)}`,
    open: false,
    eventHandler: null,
    keyHandler: null,
    
    init() {
      // Sync with global state
      this.open = Alpine.store('uiState').isDropdownOpen(this.id);
      
      // Listen for global dropdown events
      this.eventHandler = (e) => {
        if (e.detail.id !== this.id && e.detail.open) {
          this.open = false;
        }
      };
      window.addEventListener('dropdown-toggled', this.eventHandler);
      
      // Close on escape key
      this.keyHandler = (e) => {
        if (e.key === 'Escape' && this.open) {
          this.close();
        }
      };
      this.$el.addEventListener('keydown', this.keyHandler);
      
      // Register cleanup
      Alpine.store('uiState').registerCleanup(this.$el, () => {
        if (this.eventHandler) {
          window.removeEventListener('dropdown-toggled', this.eventHandler);
        }
        if (this.keyHandler) {
          this.$el.removeEventListener('keydown', this.keyHandler);
        }
      });
    },
    
    destroy() {
      Alpine.store('uiState').cleanup(this.$el);
    },
    
    toggle() {
      this.open = !this.open;
      Alpine.store('uiState').toggleDropdown(this.id);
    },
    
    close() {
      this.open = false;
      Alpine.store('uiState').closeDropdown(this.id);
    },
    
    clickOutside() {
      if (this.open) {
        this.close();
      }
    }
  }));
  
  // Checkbox group component
  Alpine.data('checkboxGroup', () => ({
    values: [],
    
    toggle(value) {
      const index = this.values.indexOf(value);
      if (index > -1) {
        this.values.splice(index, 1);
      } else {
        this.values.push(value);
      }
    },
    
    isChecked(value) {
      return this.values.includes(value);
    },
    
    selectAll(options) {
      this.values = [...options];
    },
    
    deselectAll() {
      this.values = [];
    }
  }));
  
  // Tab component
  Alpine.data('tabs', (defaultTab = null) => ({
    activeTab: defaultTab,
    
    init() {
      // Set first tab as active if none specified
      if (!this.activeTab) {
        const firstTab = this.$el.querySelector('[role="tab"]');
        if (firstTab) {
          this.activeTab = firstTab.getAttribute('data-tab-id');
        }
      }
      
      // Restore from URL if present (with compatibility check)
      if (typeof URLSearchParams !== 'undefined') {
        try {
          const urlParams = new URLSearchParams(window.location.search);
          const tabFromUrl = urlParams.get('tab');
          if (tabFromUrl) {
            this.activeTab = tabFromUrl;
          }
        } catch (e) {
          console.warn('URLSearchParams not supported:', e);
        }
      }
    },
    
    selectTab(tabId) {
      this.activeTab = tabId;
      
      // Update URL without reload (with compatibility check)
      if (typeof URL !== 'undefined' && window.history && window.history.pushState) {
        try {
          const url = new URL(window.location);
          url.searchParams.set('tab', tabId);
          window.history.pushState({}, '', url);
        } catch (e) {
          console.warn('URL manipulation not supported:', e);
        }
      }
      
      // Emit event
      this.$dispatch('tab-changed', { tab: tabId });
    },
    
    isActive(tabId) {
      return this.activeTab === tabId;
    }
  }));
});

/**
 * Global Event Handlers
 */

// Close all dropdowns on escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && typeof Alpine !== 'undefined' && Alpine.store) {
    Alpine.store('uiState').closeAllDropdowns();
  }
});

// Handle Livewire navigation events
document.addEventListener('livewire:navigated', () => {
  // Livewire v3 automatically re-initializes Alpine components
  // Just restore UI state
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    Alpine.store('uiState').restore();
  }
});

// Handle browser back/forward
window.addEventListener('popstate', () => {
  // Update tab state from URL (with compatibility check)
  if (typeof URLSearchParams !== 'undefined') {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const tab = urlParams.get('tab');
      if (tab) {
        // Dispatch event to update tab components
        window.dispatchEvent(new CustomEvent('url-tab-changed', { detail: { tab } }));
      }
    } catch (e) {
      console.warn('URLSearchParams not supported:', e);
    }
  }
});

/**
 * Utility Functions
 */
window.AskProAI = window.AskProAI || {};

window.AskProAI.UI = {
  /**
   * Show a temporary notification
   */
  notify(message, type = 'info', duration = 3000) {
    // Try Filament notifications first
    if (typeof window.FilamentNotifications !== 'undefined') {
      window.FilamentNotifications.notify(type, message);
    } 
    // Fallback to native notification if available
    else if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
      new Notification(type === 'error' ? 'Error' : 'Success', {
        body: message,
        icon: `/icons/${type}.svg`
      });
    }
    // Final fallback to console
    else {
      console.log(`[${type.toUpperCase()}] ${message}`);
    }
  },
  
  /**
   * Confirm an action
   */
  confirm(message, onConfirm, onCancel = null) {
    // Try Filament modal first
    if (typeof window.FilamentModals !== 'undefined') {
      window.FilamentModals.confirm(message, onConfirm, onCancel);
    }
    // Fallback to native confirm
    else if (window.confirm(message)) {
      onConfirm();
    } else if (onCancel) {
      onCancel();
    }
  },
  
  /**
   * Toggle dark mode
   */
  toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    
    if (isDark) {
      html.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    } else {
      html.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    }
  }
};

// Initialize theme from localStorage
(function() {
  try {
    const theme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (theme === 'dark' || (!theme && prefersDark)) {
      document.documentElement.classList.add('dark');
    }
  } catch (e) {
    console.warn('Theme initialization failed:', e);
  }
})();