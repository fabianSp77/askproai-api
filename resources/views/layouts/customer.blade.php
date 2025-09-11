<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Customer Portal') - {{ config('app.name') }}</title>
    
    {{-- Styles --}}
    @vite(['resources/css/app.css'])
    @livewireStyles
    
    {{-- Alpine.js for interactivity --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="antialiased bg-gray-50">
    {{-- Navigation --}}
    <nav class="bg-white border-b border-gray-200" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    {{-- Logo --}}
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('customer.dashboard') }}">
                            <img class="h-8 w-auto" src="/logo.svg" alt="{{ config('app.name') }}">
                        </a>
                    </div>
                    
                    {{-- Desktop Navigation --}}
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('customer.dashboard') }}" 
                           class="{{ request()->routeIs('customer.dashboard') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('customer.transactions') }}" 
                           class="{{ request()->routeIs('customer.transactions*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Transaktionen
                        </a>
                        <a href="{{ route('customer.calls') }}" 
                           class="{{ request()->routeIs('customer.calls*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Anrufe
                        </a>
                        <a href="{{ route('customer.billing') }}" 
                           class="{{ request()->routeIs('customer.billing*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Abrechnung
                        </a>
                        <a href="{{ route('customer.settings') }}" 
                           class="{{ request()->routeIs('customer.settings*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Einstellungen
                        </a>
                    </div>
                </div>
                
                {{-- Right side --}}
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    {{-- Balance Display --}}
                    <div class="mr-4 px-3 py-1 bg-gray-100 rounded-lg">
                        <span class="text-sm text-gray-600">Guthaben:</span>
                        <span class="font-semibold text-gray-900" id="header-balance">
                            {{ number_format(Auth::user()->tenant->balance_cents / 100, 2, ',', '.') }} €
                        </span>
                    </div>
                    
                    {{-- Profile dropdown --}}
                    <div class="ml-3 relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="sr-only">Open user menu</span>
                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-white font-medium">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition
                             class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100">
                            <div class="px-4 py-3">
                                <p class="text-sm">Angemeldet als</p>
                                <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('customer.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profil</a>
                                <a href="{{ route('customer.api-keys') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">API-Schlüssel</a>
                            </div>
                            <div class="py-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Abmelden
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Mobile menu button --}}
                <div class="-mr-2 flex items-center sm:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                        <span class="sr-only">Open main menu</span>
                        <svg x-show="!mobileMenuOpen" class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="mobileMenuOpen" class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Mobile menu --}}
        <div x-show="mobileMenuOpen" class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('customer.dashboard') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('customer.dashboard') ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium">
                    Dashboard
                </a>
                <a href="{{ route('customer.transactions') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('customer.transactions*') ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium">
                    Transaktionen
                </a>
                <a href="{{ route('customer.calls') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('customer.calls*') ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium">
                    Anrufe
                </a>
                <a href="{{ route('customer.billing') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('customer.billing*') ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium">
                    Abrechnung
                </a>
            </div>
        </div>
    </nav>
    
    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>
    
    {{-- Footer --}}
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center text-sm text-gray-500">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Alle Rechte vorbehalten.
            </div>
        </div>
    </footer>
    
    {{-- Scripts --}}
    @livewireScripts
    @vite(['resources/js/app.js'])
    @stack('scripts')
    
    {{-- Notifications --}}
    <x-notification-toast />
</body>
</html>