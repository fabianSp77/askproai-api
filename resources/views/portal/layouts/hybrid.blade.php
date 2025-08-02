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
    
    <!-- Alpine Components -->
    <script src="{{ mix('js/alpine-portal.js') }}"></script>
    
    <!-- React for complex components -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    
    <!-- Day.js -->
    <script src="https://unpkg.com/dayjs@1.11.7/dayjs.min.js"></script>
    <script src="https://unpkg.com/dayjs@1.11.7/locale/de.js"></script>
    <script>dayjs.locale('de')</script>
    
    <!-- Hybrid Bridge -->
    <script>
        // Bridge between Alpine and React
        window.HybridBridge = {
            // Alpine to React communication
            sendToReact(eventName, data) {
                window.dispatchEvent(new CustomEvent('alpine-to-react', {
                    detail: { event: eventName, data }
                }));
            },
            
            // React to Alpine communication
            sendToAlpine(eventName, data) {
                if (window.Alpine && window.Alpine.store('portal')) {
                    window.Alpine.store('portal').emit(eventName, data);
                }
            },
            
            // Get Alpine store data for React
            getAlpineStore(storeName = 'portal') {
                return window.Alpine ? window.Alpine.store(storeName) : null;
            },
            
            // Share state between Alpine and React
            sharedState: new Proxy({}, {
                set(target, prop, value) {
                    target[prop] = value;
                    // Notify both Alpine and React
                    window.HybridBridge.sendToReact('state-changed', { prop, value });
                    window.HybridBridge.sendToAlpine('state-changed', { prop, value });
                    return true;
                }
            })
        };
        
        // Initialize after Alpine
        document.addEventListener('alpine:init', () => {
            // Make Alpine store available to React
            window.AlpinePortalStore = Alpine.store('portal');
            
            // Listen for React events in Alpine
            window.addEventListener('react-to-alpine', (e) => {
                Alpine.store('portal').emit(e.detail.event, e.detail.data);
            });
        });
    </script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div x-data="{ 
            sidebarOpen: false,
            reactComponents: [],
            mountReactComponent(componentName, props, elementId) {
                this.reactComponents.push({ componentName, props, elementId });
                this.$nextTick(() => {
                    window.mountReactComponent?.(componentName, props, elementId);
                });
            }
         }" 
         class="min-h-screen">
        
        <!-- Mobile sidebar backdrop (Alpine) -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-gray-600 bg-opacity-75 md:hidden z-40"></div>
        
        <!-- Sidebar (Alpine) -->
        @include('portal.partials.hybrid-sidebar')
        
        <!-- Main Content -->
        <div class="md:pl-64 flex flex-col flex-1">
            <!-- Top Bar (Alpine + React hybrid) -->
            <header class="bg-white shadow-sm border-b">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <!-- Mobile menu button (Alpine) -->
                    <button @click="sidebarOpen = true" class="md:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    <!-- Search (React Component) -->
                    <div class="flex-1 max-w-lg mx-4">
                        <div id="react-search-component"></div>
                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                // Mount React search component
                                window.mountReactComponent?.('SearchComponent', {
                                    placeholder: 'Suchen...',
                                    onSelect: (result) => {
                                        window.HybridBridge.sendToAlpine('search-selected', result);
                                    }
                                }, 'react-search-component');
                            });
                        </script>
                    </div>
                    
                    <!-- Right side items -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications (Alpine) -->
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
                            
                            @include('portal.partials.notification-dropdown')
                        </div>
                        
                        <!-- User Menu (Alpine) -->
                        @include('portal.partials.user-menu')
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="flex-1">
                <!-- Flash Messages (Alpine) -->
                @include('portal.partials.flash-messages')
                
                <!-- Content area can use both Alpine and React -->
                <div class="hybrid-content">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    
    <!-- Toast Container (Alpine) -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <!-- React Component Registry -->
    <script>
        // Registry for React components
        window.ReactComponents = {};
        
        // Helper to mount React components
        window.mountReactComponent = (componentName, props = {}, elementId) => {
            const element = document.getElementById(elementId);
            if (!element) {
                console.error(`Element with id ${elementId} not found`);
                return;
            }
            
            const Component = window.ReactComponents[componentName];
            if (!Component) {
                console.error(`React component ${componentName} not registered`);
                return;
            }
            
            // Add bridge to props
            const enhancedProps = {
                ...props,
                bridge: window.HybridBridge,
                alpineStore: window.AlpinePortalStore
            };
            
            ReactDOM.render(React.createElement(Component, enhancedProps), element);
        };
        
        // Example: Register components (would be loaded from separate files)
        window.ReactComponents.SearchComponent = function SearchComponent({ placeholder, onSelect, bridge, alpineStore }) {
            const [query, setQuery] = React.useState('');
            const [results, setResults] = React.useState([]);
            const [loading, setLoading] = React.useState(false);
            
            // Listen for Alpine events
            React.useEffect(() => {
                const handleAlpineEvent = (e) => {
                    if (e.detail.event === 'clear-search') {
                        setQuery('');
                        setResults([]);
                    }
                };
                window.addEventListener('alpine-to-react', handleAlpineEvent);
                return () => window.removeEventListener('alpine-to-react', handleAlpineEvent);
            }, []);
            
            const handleSearch = React.useCallback(async (searchQuery) => {
                if (searchQuery.length < 2) {
                    setResults([]);
                    return;
                }
                
                setLoading(true);
                try {
                    // Use Alpine's API helper
                    const response = await fetch(`/api/search?q=${encodeURIComponent(searchQuery)}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    setResults(data.results || []);
                } catch (error) {
                    console.error('Search error:', error);
                    setResults([]);
                } finally {
                    setLoading(false);
                }
            }, []);
            
            // Debounced search
            React.useEffect(() => {
                const timer = setTimeout(() => {
                    handleSearch(query);
                }, 300);
                return () => clearTimeout(timer);
            }, [query, handleSearch]);
            
            return React.createElement('div', { className: 'relative' },
                React.createElement('input', {
                    type: 'text',
                    value: query,
                    onChange: (e) => setQuery(e.target.value),
                    placeholder: placeholder,
                    className: 'w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500'
                }),
                React.createElement('div', { 
                    className: 'absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none' 
                },
                    React.createElement('svg', { 
                        className: 'h-5 w-5 text-gray-400', 
                        fill: 'none', 
                        stroke: 'currentColor', 
                        viewBox: '0 0 24 24' 
                    },
                        React.createElement('path', {
                            strokeLinecap: 'round',
                            strokeLinejoin: 'round',
                            strokeWidth: 2,
                            d: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'
                        })
                    )
                ),
                loading && React.createElement('div', { 
                    className: 'absolute right-3 top-3' 
                }, 
                    React.createElement('svg', { 
                        className: 'animate-spin h-4 w-4 text-gray-400', 
                        fill: 'none', 
                        viewBox: '0 0 24 24' 
                    },
                        React.createElement('circle', {
                            className: 'opacity-25',
                            cx: '12',
                            cy: '12',
                            r: '10',
                            stroke: 'currentColor',
                            strokeWidth: '4'
                        }),
                        React.createElement('path', {
                            className: 'opacity-75',
                            fill: 'currentColor',
                            d: 'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z'
                        })
                    )
                ),
                results.length > 0 && React.createElement('div', {
                    className: 'absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 max-h-96 overflow-y-auto z-50'
                },
                    results.map((result, index) => 
                        React.createElement('button', {
                            key: result.id,
                            onClick: () => {
                                onSelect(result);
                                setQuery('');
                                setResults([]);
                            },
                            className: 'w-full text-left px-4 py-3 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none'
                        },
                            React.createElement('div', { className: 'font-medium text-gray-900' }, result.title),
                            React.createElement('div', { className: 'text-sm text-gray-500' }, result.subtitle)
                        )
                    )
                )
            );
        };
    </script>
    
    <!-- Scripts -->
    <script src="{{ mix('js/app.js') }}"></script>
    @stack('scripts')
    
    <!-- Performance hint -->
    <script>
        // Log enhancement level for debugging
        console.log('Running in hybrid mode (Level 3): Alpine.js + React');
        
        // Measure performance
        if (window.performance && window.performance.mark) {
            window.performance.mark('hybrid-loaded');
            window.performance.measure('hybrid-load-time', 'navigationStart', 'hybrid-loaded');
            const measure = window.performance.getEntriesByName('hybrid-load-time')[0];
            console.log(`Hybrid mode loaded in ${Math.round(measure.duration)}ms`);
        }
    </script>
</body>
</html>