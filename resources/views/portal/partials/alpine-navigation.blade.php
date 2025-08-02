{{-- Alpine.js Enhanced Navigation --}}
<a href="{{ route('portal.dashboard') }}" 
   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
    <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.dashboard') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
    </svg>
    Dashboard
</a>

<a href="{{ route('portal.calls.index') }}" 
   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.calls.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
    <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.calls.*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
    </svg>
    Anrufe
    <span x-data="{ count: 0 }" 
          x-init="
              // Listen for real-time updates
              Alpine.store('portal').echo?.channel('portal.{{ auth()->user()->company_id }}')
                  .listen('CallReceived', (e) => {
                      count++;
                      setTimeout(() => count--, 60000); // Remove after 1 minute
                  });
          "
          x-show="count > 0"
          x-text="count"
          class="ml-auto inline-block py-0.5 px-2 text-xs font-medium rounded-full bg-red-100 text-red-800"></span>
</a>

<a href="{{ route('portal.appointments.index') }}" 
   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.appointments.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
    <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.appointments.*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
    </svg>
    Termine
    <span x-data="{ 
            today: 0,
            updateCount() {
                // This would be populated from portal store
                this.today = Alpine.store('portal').todayAppointments || 0;
            }
          }" 
          x-init="updateCount(); setInterval(() => updateCount(), 60000)"
          x-show="today > 0"
          x-text="today"
          class="ml-auto inline-block py-0.5 px-2 text-xs font-medium rounded-full bg-blue-100 text-blue-800"></span>
</a>

<a href="{{ route('portal.customers.index') }}" 
   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.customers.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
    <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.customers.*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
    </svg>
    Kunden
</a>

<div class="pt-5">
    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Verwaltung</p>
    
    <a href="{{ route('portal.team.index') }}" 
       class="mt-2 group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.team.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.team.*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        Team
    </a>
    
    <a href="{{ route('portal.billing.index') }}" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.billing.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.billing.*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
        Abrechnung
    </a>
    
    <a href="{{ route('portal.settings') }}" 
       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('portal.settings*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('portal.settings*') ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        Einstellungen
    </a>
</div>

{{-- Quick Actions --}}
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
                <a href="{{ route('portal.appointments.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Neuer Termin
                </a>
                <a href="{{ route('portal.customers.create') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    Neuer Kunde
                </a>
                <a href="{{ route('portal.team.invite') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="inline-block w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Mitarbeiter einladen
                </a>
            </div>
        </div>
    </div>
</div>