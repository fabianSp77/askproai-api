{{-- Simple Mobile Navigation Button --}}
<div class="lg:hidden">
    <button
        @click="$store.sidebar.toggle()"
        type="button"
        class="relative inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        aria-label="Menü öffnen"
    >
        <span class="sr-only">Hauptmenü öffnen</span>
        
        {{-- Hamburger Icon --}}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</div>