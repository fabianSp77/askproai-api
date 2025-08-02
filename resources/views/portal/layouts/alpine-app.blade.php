<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name', 'Portal')) - {{ auth()->user()->company->name ?? 'AskProAI' }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    @stack('styles')
    
    <!-- Alpine Plugins -->
    <script defer src="https://unpkg.com/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Alpine Core -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Initialize Alpine Components -->
    <script src="{{ mix('js/alpine-portal.js') }}"></script>
    
    <!-- Day.js for date handling -->
    <script src="https://unpkg.com/dayjs@1.11.7/dayjs.min.js"></script>
    <script src="https://unpkg.com/dayjs@1.11.7/locale/de.js"></script>
    <script>dayjs.locale('de')</script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-gray-600 bg-opacity-75 md:hidden z-40"></div>
        
        <!-- Sidebar -->
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
            
            <!-- Branch Selector -->
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
                    @include('portal.partials.alpine-navigation')
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="md:pl-64 flex flex-col flex-1">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm border-b">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <!-- Mobile menu button -->
                    <button @click="sidebarOpen = true" class="md:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <!-- Search -->
                    <div class="flex-1 max-w-lg mx-4" x-data="search">
                        <div class="relative">
                            <input type="text"
                                   x-model="query"
                                   @input="handleInput"
                                   @keydown="handleKeydown"
                                   @focus="focused = true"
                                   @blur="setTimeout(() => focused = false, 200)"
                                   x-ref="input"
                                   placeholder="Suchen..."
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            
                            <!-- Search Results -->
                            <div x-show="focused && results.length > 0"
                                 x-transition
                                 x-ref="results"
                                 class="absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 max-h-96 overflow-y-auto z-50">
                                <template x-for="(result, index) in results" :key="result.id">
                                    <button @click="selectResult(result)"
                                            @mouseenter="selectedIndex = index"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                                            :class="{ 'bg-gray-50': selectedIndex === index }">
                                        <div class="flex items-center">
                                            <span class="text-xl mr-3" x-text="getResultIcon(result.type)"></span>
                                            <div>
                                                <div class="font-medium text-gray-900" x-html="highlightMatch(result.title)"></div>
                                                <div class="text-sm text-gray-500" x-html="highlightMatch(result.subtitle)"></div>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right side items -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div x-data="notifications" class="relative">
                            <button @click="toggle()"
                                    class="relative p-2 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                <span x-show="unreadCount > 0"
                                      x-text="unreadCount"
                                      class="absolute top-0 right-0 -mt-1 -mr-1 px-2 py-1 text-xs font-medium text-white bg-red-500 rounded-full"></span>
                            </button>
                            
                            <!-- Notifications Dropdown -->
                            <div x-show="isOpen"
                                 x-transition
                                 @click.away="portal.notificationsOpen = false"
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                <div class="py-2">
                                    <div class="px-4 py-2 border-b">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-medium text-gray-900">Benachrichtigungen</h3>
                                            <button @click="markAllAsRead()"
                                                    x-show="unreadCount > 0"
                                                    class="text-xs text-blue-600 hover:text-blue-800">
                                                Alle als gelesen markieren
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="max-h-96 overflow-y-auto">
                                        <template x-for="notification in notifications" :key="notification.id">
                                            <div @click="handleAction(notification)"
                                                 class="px-4 py-3 hover:bg-gray-50 cursor-pointer"
                                                 :class="{ 'bg-blue-50': !notification.read_at }">
                                                <div class="flex items-start">
                                                    <span class="text-2xl mr-3" x-text="getIcon(notification.type)"></span>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-medium text-gray-900" x-text="notification.title"></p>
                                                        <p class="text-xs text-gray-500 mt-1" x-text="notification.message"></p>
                                                        <p class="text-xs text-gray-400 mt-1" x-text="getTimeAgo(notification.created_at)"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <div x-show="notifications.length === 0" class="px-4 py-8 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="mt-2 text-sm">Keine Benachrichtigungen</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div x-data="dropdown" class="relative">
                            <button @click="toggle()"
                                    class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <img class="h-8 w-8 rounded-full" 
                                     src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}" 
                                     alt="{{ auth()->user()->name }}">
                            </button>
                            
                            <div x-show="open"
                                 x-transition
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
                                <div class="py-1">
                                    <div class="px-4 py-2 text-xs text-gray-500">
                                        {{ auth()->user()->name }}<br>
                                        {{ auth()->user()->email }}
                                    </div>
                                    <div class="border-t"></div>
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
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1">
                @if(session('success'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-init="setTimeout(() => show = false, 5000)"
                         class="fixed top-20 right-4 z-50">
                        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-md shadow-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button @click="show = false" class="text-green-400 hover:text-green-500">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if(session('error'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-init="setTimeout(() => show = false, 5000)"
                         class="fixed top-20 right-4 z-50">
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button @click="show = false" class="text-red-400 hover:text-red-500">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <!-- Scripts -->
    <script src="{{ mix('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>