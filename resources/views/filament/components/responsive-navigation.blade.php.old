@php
    // Safely get branch context
    try {
        $branchContext = app(\App\Services\BranchContextManager::class);
        $currentBranch = $branchContext->getCurrentBranch();
        $isAllBranches = $branchContext->isAllBranchesView();
        $branches = $branchContext->getBranchesForUser();
    } catch (\Exception $e) {
        $currentBranch = null;
        $isAllBranches = false;
        $branches = collect();
    }
@endphp

<div class="responsive-navigation">
    {{-- Mobile Burger Menu Button (visible on mobile only) --}}
    <div class="lg:hidden absolute left-4 top-4 z-50">
        <button
            x-data="{ open: false }"
            @click="$dispatch('toggle-mobile-sidebar')"
            type="button"
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:text-gray-300 dark:hover:text-gray-200 dark:hover:bg-gray-700"
            aria-label="{{ __('Menü öffnen') }}"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    {{-- Desktop Branch Switcher (visible on desktop only) --}}
    <div class="hidden lg:block">
        @include('filament.components.professional-branch-switcher')
    </div>

    {{-- Mobile Branch Switcher (integrated into mobile menu) --}}
    <script>
        // Handle mobile sidebar toggle
        document.addEventListener('alpine:init', () => {
            Alpine.data('mobileSidebar', () => ({
                open: false,
                toggle() {
                    this.open = !this.open;
                    // Toggle body scroll
                    if (this.open) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
            }));
        });

        // Listen for toggle event
        window.addEventListener('toggle-mobile-sidebar', () => {
            const sidebar = document.querySelector('.fi-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        });
    </script>
</div>

<style>
    /* Mobile Sidebar Styles */
    @media (max-width: 1023px) {
        .fi-sidebar {
            @apply fixed inset-y-0 left-0 z-40 w-64 transform -translate-x-full transition-transform duration-300 ease-in-out;
        }
        
        .fi-sidebar.mobile-open {
            @apply translate-x-0;
        }
        
        /* Backdrop */
        .fi-sidebar.mobile-open::after {
            content: '';
            @apply fixed inset-0 bg-black bg-opacity-50 z-[-1];
        }
        
        /* Ensure content is scrollable */
        .fi-sidebar-content {
            @apply overflow-y-auto h-full;
        }
        
        /* Add close button to sidebar on mobile */
        .fi-sidebar-header::after {
            content: '×';
            @apply absolute right-4 top-4 text-2xl cursor-pointer text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
        }
    }
    
    /* Enhanced Mobile Experience */
    @media (max-width: 640px) {
        /* Full width dropdowns on mobile */
        .branch-switcher-dropdown {
            @apply w-screen max-w-none;
            left: 0 !important;
            right: 0 !important;
            margin: 0 !important;
        }
        
        /* Larger touch targets */
        .branch-switcher-item {
            @apply py-3;
        }
    }
    
    /* Smooth transitions */
    .responsive-navigation * {
        @apply transition-all duration-200 ease-in-out;
    }
</style>