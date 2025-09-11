@php
    use Filament\Support\Enums\MaxWidth;
    $navigation = filament()->getNavigation();
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div
        x-data="stripeMenuSystem()"
        class="fi-layout stripe-layout min-h-screen w-full"
    >
        {{-- OVERLAY FOR MOBILE --}}
        @if (filament()->hasNavigation())
            <div
                x-cloak
                x-on:click="closeSidebar()"
                x-show="isMobile && sidebarOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fi-sidebar-close-overlay fixed inset-0 z-[9998] bg-gray-950/50 lg:hidden"
            ></div>

            {{-- SIDEBAR WITH STRIPE-LIKE FEATURES --}}
            <aside
                x-bind:class="{
                    'translate-x-0': sidebarOpen || !isMobile,
                    '-translate-x-full': !sidebarOpen && isMobile,
                    'fi-sidebar-collapsed': sidebarCollapsed && !isMobile,
                    'fi-sidebar-expanded': !sidebarCollapsed && !isMobile
                }"
                class="fi-sidebar stripe-sidebar transition-all duration-300 ease-in-out"
            >
                <x-filament-panels::sidebar
                    :navigation="$navigation"
                    class="fi-main-sidebar h-full"
                />
                
                {{-- DESKTOP COLLAPSE TOGGLE --}}
                <button
                    x-show="!isMobile"
                    x-on:click="toggleSidebarCollapse()"
                    class="sidebar-collapse-btn hidden lg:flex"
                    x-bind:title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                >
                    <svg x-show="!sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                    <svg x-show="sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                </button>
            </aside>
        @endif

        {{-- MAIN CONTENT AREA --}}
        <div
            x-bind:class="{
                'stripe-main-expanded': !sidebarCollapsed && !isMobile,
                'stripe-main-collapsed': sidebarCollapsed && !isMobile,
                'stripe-main-mobile': isMobile
            }"
            class="fi-main-ctn stripe-main-content"
        >
            @if (filament()->hasTopbar())
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_BEFORE, scopes: $livewire->getRenderHookScopes()) }}
                
                {{-- MOBILE MENU BUTTON IN TOPBAR --}}
                <div class="fi-topbar stripe-topbar">
                    <button
                        x-show="isMobile"
                        x-on:click="toggleSidebar()"
                        class="mobile-menu-toggle lg:hidden"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <x-filament-panels::topbar :navigation="$navigation" />
                </div>
                
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::TOPBAR_AFTER, scopes: $livewire->getRenderHookScopes()) }}
            @endif

            <main
                @class([
                    'fi-main stripe-main mx-auto h-full w-full px-4 md:px-6 lg:px-8',
                    match ($maxContentWidth ??= (filament()->getMaxContentWidth() ?? MaxWidth::SevenExtraLarge)) {
                        MaxWidth::ExtraSmall, 'xs' => 'max-w-xs',
                        MaxWidth::Small, 'sm' => 'max-w-sm',
                        MaxWidth::Medium, 'md' => 'max-w-md',
                        MaxWidth::Large, 'lg' => 'max-w-lg',
                        MaxWidth::ExtraLarge, 'xl' => 'max-w-xl',
                        MaxWidth::TwoExtraLarge, '2xl' => 'max-w-2xl',
                        MaxWidth::ThreeExtraLarge, '3xl' => 'max-w-3xl',
                        MaxWidth::FourExtraLarge, '4xl' => 'max-w-4xl',
                        MaxWidth::FiveExtraLarge, '5xl' => 'max-w-5xl',
                        MaxWidth::SixExtraLarge, '6xl' => 'max-w-6xl',
                        MaxWidth::SevenExtraLarge, '7xl' => 'max-w-7xl',
                        MaxWidth::Full, 'full' => 'max-w-full',
                        default => $maxContentWidth,
                    },
                ])
            >
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, scopes: $livewire->getRenderHookScopes()) }}
                
                {{ $slot }}
                
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::CONTENT_END, scopes: $livewire->getRenderHookScopes()) }}
            </main>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::FOOTER, scopes: $livewire->getRenderHookScopes()) }}
        </div>
    </div>

    {{-- STRIPE-LIKE MENU STYLES --}}
    <style>
        /* Stripe-like Layout System */
        .stripe-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Styles */
        .stripe-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background: linear-gradient(to bottom, #ffffff, #f9fafb);
            border-right: 1px solid #e5e7eb;
            z-index: 9999;
            overflow-y: auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Desktop Expanded Sidebar */
        @media (min-width: 1024px) {
            .stripe-sidebar.fi-sidebar-expanded {
                width: 16rem;
                transform: translateX(0);
            }

            .stripe-sidebar.fi-sidebar-collapsed {
                width: 4rem;
                transform: translateX(0);
            }

            .stripe-sidebar.fi-sidebar-collapsed .fi-sidebar-item-label,
            .stripe-sidebar.fi-sidebar-collapsed .fi-sidebar-group-label {
                display: none;
            }

            .stripe-sidebar.fi-sidebar-collapsed .fi-sidebar-item {
                justify-content: center;
            }
        }

        /* Mobile Sidebar */
        @media (max-width: 1023px) {
            .stripe-sidebar {
                width: 16rem;
                max-width: 80vw;
                box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
            }
        }

        /* Main Content Area */
        .stripe-main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f9fafb;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Desktop Main Content Positioning */
        @media (min-width: 1024px) {
            .stripe-main-expanded {
                margin-left: 16rem;
            }

            .stripe-main-collapsed {
                margin-left: 4rem;
            }
        }

        /* Mobile Main Content */
        @media (max-width: 1023px) {
            .stripe-main-mobile {
                margin-left: 0;
                width: 100%;
            }
        }

        /* Sidebar Collapse Button */
        .sidebar-collapse-btn {
            position: absolute;
            right: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 48px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0 0.375rem 0.375rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 10000;
        }

        .sidebar-collapse-btn:hover {
            background: #f3f4f6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 9997;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .mobile-menu-toggle:hover {
            background: #f9fafb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Morphing Animation for Menu Items */
        .fi-sidebar-item {
            position: relative;
            overflow: hidden;
        }

        .fi-sidebar-item::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(14, 165, 233, 0.1);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        .fi-sidebar-item:hover::before {
            width: 100%;
            height: 100%;
            border-radius: 0.5rem;
        }

        /* Stripe-like Hover Effects */
        .fi-sidebar-item a {
            position: relative;
            z-index: 1;
        }

        /* Smooth Transitions */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Fix Z-index Hierarchy */
        .fi-sidebar-close-overlay { z-index: 9998; }
        .stripe-sidebar { z-index: 9999; }
        .sidebar-collapse-btn { z-index: 10000; }
        .mobile-menu-toggle { z-index: 9997; }
        
        /* Ensure content is never hidden */
        .stripe-main-content {
            position: relative;
            z-index: 1;
            min-height: 100vh;
        }
    </style>

    {{-- ALPINE.JS STRIPE MENU CONTROLLER --}}
    <script>
        function stripeMenuSystem() {
            return {
                sidebarOpen: false,
                sidebarCollapsed: false,
                isMobile: window.innerWidth < 1024,
                
                init() {
                    // Check for saved preference
                    const saved = localStorage.getItem('sidebarCollapsed');
                    if (saved !== null) {
                        this.sidebarCollapsed = saved === 'true';
                    }
                    
                    // Handle resize
                    window.addEventListener('resize', () => {
                        this.isMobile = window.innerWidth < 1024;
                        if (!this.isMobile) {
                            this.sidebarOpen = false;
                        }
                    });
                    
                    // Set up Livewire/Alpine store integration
                    this.$watch('sidebarOpen', value => {
                        if (window.Alpine && window.Alpine.store('sidebar')) {
                            window.Alpine.store('sidebar').isOpen = value;
                        }
                    });
                    
                    // Sync with Filament's sidebar state
                    if (window.Alpine && window.Alpine.store('sidebar')) {
                        this.sidebarOpen = window.Alpine.store('sidebar').isOpen;
                    }
                },
                
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },
                
                closeSidebar() {
                    this.sidebarOpen = false;
                },
                
                toggleSidebarCollapse() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
                },
                
                // Morphing menu animation helper
                morphToSection(sectionId) {
                    const section = document.querySelector(sectionId);
                    if (section) {
                        // Implement Stripe-like morphing animation
                        section.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }
        }
    </script>
</x-filament-panels::layout.base>