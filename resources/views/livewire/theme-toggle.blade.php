{{-- Theme Toggle Component
    Provides light/dark mode switching with localStorage persistence
    Uses Alpine.js for client-side logic and smooth transitions

    Features:
    - Toggle button (☀️ / 🌙)
    - localStorage persistence
    - System preference respect (prefers-color-scheme)
    - Smooth CSS transitions
--}}

<div x-data="themeToggle()"
     x-init="init()"
     class="theme-toggle-wrapper">

    {{-- Toggle Button --}}
    <button @click="toggle()"
            type="button"
            :title="isDark ? 'Light mode' : 'Dark mode'"
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            class="theme-toggle"
            :class="{ 'dark': isDark }">

        {{-- Sun Icon (Light Mode) --}}
        <svg x-show="!isDark"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-0"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-0"
             class="theme-toggle-icon"
             xmlns="http://www.w3.org/2000/svg"
             fill="currentColor"
             viewBox="0 0 24 24">
            <path d="M12 18a6 6 0 100-12 6 6 0 000 12zm0-2a4 4 0 110-8 4 4 0 010 8zm0-10a1 1 0 011-1h.01a1 1 0 110-2h-.01a1 1 0 01-1 1zm6.707-3.293a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM18 11a1 1 0 100 2h.01a1 1 0 100-2h-.01zm2.121 2.121a1 1 0 01-1.414 0l-.707-.707a1 1 0 111.414-1.414l.707.707a1 1 0 010 1.414zM6.707 4.707a1 1 0 010 1.414L6 6.121A1 1 0 114.586 4.707l.707-.707a1 1 0 011.414 0zM4 12a1 1 0 100 2H2a1 1 0 100-2h2zm0-7a1 1 0 011-1h.01a1 1 0 110 2h-.01a1 1 0 01-1-1zm1.929 10.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 111.414 1.414l-.707.707z"/>
        </svg>

        {{-- Moon Icon (Dark Mode) --}}
        <svg x-show="isDark"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform scale-0"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-0"
             class="theme-toggle-icon"
             xmlns="http://www.w3.org/2000/svg"
             fill="currentColor"
             viewBox="0 0 24 24">
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
        </svg>
    </button>
</div>

{{-- Alpine.js Component Logic --}}
<script>
function themeToggle() {
    return {
        isDark: false,

        /**
         * Initialize theme
         * 1. Check localStorage for saved preference
         * 2. Fall back to system preference (prefers-color-scheme)
         * 3. Fall back to light mode as default
         */
        init() {
            const saved = localStorage.getItem('theme');

            if (saved) {
                // Use saved preference
                this.isDark = saved === 'dark';
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                // Respect system preference
                this.isDark = true;
            } else {
                // Default to light mode
                this.isDark = false;
            }

            // Apply theme
            this.applyTheme();

            // Listen for system theme changes
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (!localStorage.getItem('theme')) {
                        this.isDark = e.matches;
                        this.applyTheme();
                    }
                });
            }
        },

        /**
         * Toggle theme
         */
        toggle() {
            this.isDark = !this.isDark;
            this.applyTheme();
        },

        /**
         * Apply theme to document
         * - Set HTML data-theme attribute
         * - Add/remove dark class
         * - Persist to localStorage
         */
        applyTheme() {
            const theme = this.isDark ? 'dark' : 'light';
            const html = document.documentElement;

            // Set data attribute
            html.setAttribute('data-theme', theme);

            // Set class for Tailwind dark mode
            if (this.isDark) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }

            // Save preference
            localStorage.setItem('theme', theme);

            // Emit custom event for other components
            window.dispatchEvent(new CustomEvent('theme-changed', {
                detail: { theme }
            }));
        }
    }
}

/**
 * Global setTheme function (called from Livewire)
 */
window.setTheme = function(theme) {
    const isDark = theme === 'dark';
    const html = document.documentElement;

    html.setAttribute('data-theme', theme);
    if (isDark) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }

    localStorage.setItem('theme', theme);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
};

/**
 * Get current theme
 */
window.getTheme = function() {
    return localStorage.getItem('theme') || 'light';
};
</script>
