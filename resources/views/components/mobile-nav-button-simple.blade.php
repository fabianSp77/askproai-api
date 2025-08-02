{{-- Ultra Simple Mobile Navigation Button --}}
<button
    type="button"
    onclick="toggleMobileSidebar()"
    class="lg:hidden inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-colors duration-200"
    aria-label="Toggle navigation"
>
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<script>
function toggleMobileSidebar() {
    const sidebar = document.querySelector('.fi-sidebar');
    const body = document.body;
    
    if (sidebar) {
        const isOpen = body.classList.contains('fi-sidebar-open');
        
        if (isOpen) {
            // Close sidebar
            sidebar.style.left = '-100%';
            body.classList.remove('fi-sidebar-open');
            body.style.overflow = '';
        } else {
            // Open sidebar
            sidebar.style.left = '0';
            body.classList.add('fi-sidebar-open');
            body.style.overflow = 'hidden';
        }
    }
}
</script>

<style>
/* Ensure button is visible */
@media (max-width: 1023px) {
    .fi-topbar > div:first-child {
        display: flex !important;
        align-items: center !important;
        gap: 1rem !important;
    }
}
</style>