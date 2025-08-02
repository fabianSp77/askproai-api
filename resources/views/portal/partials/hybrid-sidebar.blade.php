<aside x-data="sidebar"
       :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:inset-auto">
    
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-6 border-b">
        <a href="{{ route('portal.dashboard') }}" class="flex items-center">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto">
            <span class="ml-2 text-xl font-semibold text-gray-800">Portal</span>
        </a>
        <button @click="sidebarOpen = false" class="md:hidden text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <!-- Branch Selector (Alpine) -->
    <div class="px-6 py-4" x-data="branchSelector" x-show="isMultiBranch">
        <div x-data="dropdown" class="relative">
            <button @click="toggle()" 
                    class="w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <span x-text="currentBranchName"></span>
                <svg class="w-5 h-5 ml-2 -mr-1" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
            
            <div x-show="open" 
                 x-transition
                 @click.away="open = false"
                 class="absolute left-0 right-0 mt-2 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                <div class="py-1">
                    <template x-for="branch in branches" :key="branch.id">
                        <button @click="selectBranch(branch.id)"
                                class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                :class="{ 'bg-gray-50 font-medium': currentBranch?.id === branch.id }"
                                x-text="branch.name"></button>
                    </template>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="mt-6 px-6">
        <div class="space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('portal.dashboard') }}" 
               @click="$dispatch('navigation-clicked', { route: 'dashboard' })"
               class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.dashboard') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>
            
            <!-- Calls (React Component) -->
            <div id="nav-calls-react"></div>
            
            <!-- Appointments (Alpine) -->
            <a href="{{ route('portal.appointments.index') }}" 
               class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.appointments.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.appointments.*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Termine
                <span x-data="{ count: {{ $todayAppointments ?? 0 }} }" 
                      x-show="count > 0"
                      x-text="count"
                      class="ml-auto inline-block py-0.5 px-2 text-xs font-medium rounded-full bg-blue-100 text-blue-800"></span>
            </a>
            
            <!-- Customers (React Component) -->
            <div id="nav-customers-react"></div>
            
            <div class="pt-5">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Verwaltung</p>
                
                <!-- Team (Alpine) -->
                <a href="{{ route('portal.team.index') }}" 
                   class="mt-2 group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.team.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.team.*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Team
                </a>
                
                <!-- Billing (React Component) -->
                <div id="nav-billing-react"></div>
                
                <!-- Settings (Alpine) -->
                <a href="{{ route('portal.settings') }}" 
                   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.settings*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.settings*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Einstellungen
                </a>
            </div>
            
            <!-- Quick Actions (Alpine) -->
            <div class="mt-8 px-3">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Neu erstellen
                    </button>
                    
                    <div x-show="open"
                         x-transition
                         @click.away="open = false"
                         class="absolute bottom-full left-0 right-0 mb-2 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5">
                        <div class="py-1">
                            <a href="{{ route('portal.appointments.create') }}" 
                               @click="window.HybridBridge.sendToReact('quick-action', { action: 'create-appointment' })"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Neuer Termin
                            </a>
                            <a href="{{ route('portal.customers.create') }}" 
                               @click="window.HybridBridge.sendToReact('quick-action', { action: 'create-customer' })"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                Neuer Kunde
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Mount React navigation components
    const navItems = [
        { id: 'nav-calls-react', component: 'NavCallsItem', props: { route: '{{ route('portal.calls.index') }}', active: {{ request()->routeIs('portal.calls.*') ? 'true' : 'false' }} } },
        { id: 'nav-customers-react', component: 'NavCustomersItem', props: { route: '{{ route('portal.customers.index') }}', active: {{ request()->routeIs('portal.customers.*') ? 'true' : 'false' }} } },
        { id: 'nav-billing-react', component: 'NavBillingItem', props: { route: '{{ route('portal.billing.index') }}', active: {{ request()->routeIs('portal.billing.*') ? 'true' : 'false' }} } }
    ];
    
    navItems.forEach(item => {
        if (window.mountReactComponent) {
            window.mountReactComponent(item.component, item.props, item.id);
        }
    });
});
</script>