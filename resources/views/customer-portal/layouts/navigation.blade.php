<nav class="bg-white shadow-sm" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and Navigation -->
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ url('/meine-termine') }}" class="text-2xl font-bold text-primary">
                        <i class="fas fa-calendar-check"></i>
                        <span class="ml-2">{{ config('app.name', 'AskPro') }}</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
                    <a href="{{ url('/meine-termine') }}"
                       class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                              {{ request()->is('meine-termine*') ? 'border-primary text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Meine Termine
                    </a>
                </div>
            </div>

            <!-- User Menu -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center" x-data="{ userMenuOpen: false }">
                <div class="ml-3 relative">
                    <div>
                        <button @click="userMenuOpen = !userMenuOpen"
                                type="button"
                                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                id="user-menu-button"
                                aria-expanded="false"
                                aria-haspopup="true">
                            <span class="sr-only">Benutzermenü öffnen</span>
                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                                <span x-text="auth.user ? auth.user.name.charAt(0).toUpperCase() : '?'"></span>
                            </div>
                            <span class="ml-3 text-gray-700 font-medium" x-text="auth.user ? auth.user.name : ''"></span>
                            <i class="ml-2 fas fa-chevron-down text-gray-400"></i>
                        </button>
                    </div>

                    <!-- Dropdown Menu -->
                    <div x-show="userMenuOpen"
                         @click.away="userMenuOpen = false"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                         role="menu"
                         aria-orientation="vertical"
                         aria-labelledby="user-menu-button"
                         tabindex="-1">
                        <div class="px-4 py-2 border-b border-gray-200">
                            <p class="text-sm text-gray-500">Angemeldet als</p>
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="auth.user ? auth.user.email : ''"></p>
                        </div>
                        <button @click="logout(); window.location.href = '/kundenportal/login';"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                role="menuitem"
                                tabindex="-1">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Abmelden
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary"
                        aria-controls="mobile-menu"
                        aria-expanded="false">
                    <span class="sr-only">Hauptmenü öffnen</span>
                    <i class="fas text-xl" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="sm:hidden"
         id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ url('/meine-termine') }}"
               class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium
                      {{ request()->is('meine-termine*') ? 'bg-primary-50 border-primary text-primary' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800' }}">
                <i class="fas fa-calendar-alt mr-2"></i>
                Meine Termine
            </a>
        </div>

        <div class="pt-4 pb-3 border-t border-gray-200">
            <div class="flex items-center px-4">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                        <span x-text="auth.user ? auth.user.name.charAt(0).toUpperCase() : '?'"></span>
                    </div>
                </div>
                <div class="ml-3">
                    <div class="text-base font-medium text-gray-800" x-text="auth.user ? auth.user.name : ''"></div>
                    <div class="text-sm font-medium text-gray-500" x-text="auth.user ? auth.user.email : ''"></div>
                </div>
            </div>
            <div class="mt-3 space-y-1">
                <button @click="logout(); window.location.href = '/kundenportal/login';"
                        class="block w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Abmelden
                </button>
            </div>
        </div>
    </div>
</nav>
