<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Portal') - {{ config('app.name') }}</title>
    
    {{-- Basic styles --}}
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    
    {{-- Alpine.js Core Only --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- Basic Alpine components --}}
    <script>
        document.addEventListener('alpine:init', () => {
            // Basic store for user data
            Alpine.store('user', {
                data: @json(auth()->user()),
                company: @json(auth()->user()->company ?? null),
            });
            
            // Simple notification system
            Alpine.store('notifications', {
                items: [],
                add(message, type = 'info') {
                    const id = Date.now();
                    this.items.push({ id, message, type });
                    setTimeout(() => {
                        this.remove(id);
                    }, 5000);
                },
                remove(id) {
                    this.items = this.items.filter(item => item.id !== id);
                }
            });
            
            // Basic dropdown component
            Alpine.data('dropdown', () => ({
                open: false,
                toggle() {
                    this.open = !this.open;
                },
                close() {
                    this.open = false;
                }
            }));
            
            // Basic modal component
            Alpine.data('modal', () => ({
                open: false,
                show() {
                    this.open = true;
                },
                hide() {
                    this.open = false;
                }
            }));
            
            // Basic tabs component
            Alpine.data('tabs', (initialTab = null) => ({
                activeTab: initialTab,
                isActive(tab) {
                    return this.activeTab === tab;
                },
                setActive(tab) {
                    this.activeTab = tab;
                }
            }));
        });
    </script>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    {{-- Logo --}}
                    <div class="flex items-center">
                        <a href="{{ route('portal.dashboard') }}" class="text-xl font-semibold">
                            {{ config('app.name') }}
                        </a>
                    </div>
                    
                    {{-- User menu --}}
                    <div x-data="dropdown" class="relative">
                        <button @click="toggle()" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}" alt="{{ auth()->user()->name }}">
                            <span class="ml-2">{{ auth()->user()->name }}</span>
                            <svg class="ml-1 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" 
                             x-transition
                             @click.away="close()"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="{{ route('portal.settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Einstellungen
                            </a>
                            <form method="POST" action="{{ route('portal.logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Abmelden
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                {{-- Navigation --}}
                <nav class="flex space-x-8 border-t">
                    <a href="{{ route('portal.dashboard') }}" 
                       class="border-b-2 {{ request()->routeIs('portal.dashboard') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="{{ route('portal.calls.index') }}" 
                       class="border-b-2 {{ request()->routeIs('portal.calls.*') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                        Anrufe
                    </a>
                    <a href="{{ route('portal.appointments.index') }}" 
                       class="border-b-2 {{ request()->routeIs('portal.appointments.*') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                        Termine
                    </a>
                    <a href="{{ route('portal.customers.index') }}" 
                       class="border-b-2 {{ request()->routeIs('portal.customers.*') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                        Kunden
                    </a>
                    <a href="{{ route('portal.team.index') }}" 
                       class="border-b-2 {{ request()->routeIs('portal.team.*') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                        Team
                    </a>
                    <a href="{{ route('portal.billing.index') }}" 
                       class="border-b-2 {{ request()->routeIs('portal.billing.*') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} py-4 px-1 text-sm font-medium">
                        Abrechnung
                    </a>
                </nav>
            </div>
        </header>
        
        {{-- Main content --}}
        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                {{-- Flash messages --}}
                @if(session('success'))
                    <div x-data="{ show: true }" 
                         x-show="show"
                         x-init="setTimeout(() => show = false, 5000)"
                         class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative">
                        <span class="block sm:inline">{{ session('success') }}</span>
                        <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-green-500" viewBox="0 0 20 20">
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div x-data="{ show: true }" 
                         x-show="show"
                         x-init="setTimeout(() => show = false, 5000)"
                         class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative">
                        <span class="block sm:inline">{{ session('error') }}</span>
                        <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <svg class="fill-current h-6 w-6 text-red-500" viewBox="0 0 20 20">
                                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                            </svg>
                        </button>
                    </div>
                @endif
                
                {{-- Page content --}}
                @yield('content')
            </div>
        </main>
        
        {{-- Simple notification container --}}
        <div x-data class="fixed bottom-0 right-0 p-6 z-50 pointer-events-none">
            <template x-for="notification in $store.notifications.items" :key="notification.id">
                <div x-transition
                     class="mb-4 p-4 rounded-lg shadow-lg pointer-events-auto"
                     :class="{
                         'bg-blue-500 text-white': notification.type === 'info',
                         'bg-green-500 text-white': notification.type === 'success',
                         'bg-red-500 text-white': notification.type === 'error',
                         'bg-yellow-500 text-white': notification.type === 'warning'
                     }">
                    <div class="flex items-center justify-between">
                        <span x-text="notification.message"></span>
                        <button @click="$store.notifications.remove(notification.id)" class="ml-4 text-white hover:text-gray-200">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
        
        {{-- Footer --}}
        <footer class="bg-gray-800 text-white mt-auto">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Alle Rechte vorbehalten.
                    <span class="mx-2">|</span>
                    <a href="?enhancement_level=0" class="hover:underline">Basis-Version</a>
                    <span class="mx-2">|</span>
                    <a href="?enhancement_level=2" class="hover:underline">Erweiterte Version</a>
                </p>
            </div>
        </footer>
    </div>
    
    @stack('scripts')
</body>
</html>