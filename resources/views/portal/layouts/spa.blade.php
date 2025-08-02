<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Portal') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Preload critical assets -->
    @foreach($enhancementAssets['preload'] ?? [] as $asset)
        <link rel="preload" href="{{ asset($asset) }}" as="{{ str_ends_with($asset, '.js') ? 'script' : (str_ends_with($asset, '.css') ? 'style' : 'font') }}">
    @endforeach
    
    <!-- Initial state for React -->
    <script>
        window.__INITIAL_STATE__ = {
            user: @json(auth()->user()),
            company: @json(auth()->user()->company ?? null),
            branches: @json(auth()->user()->company->branches ?? []),
            csrfToken: '{{ csrf_token() }}',
            apiBase: '{{ config('app.url') }}/api',
            pusherKey: '{{ config('broadcasting.connections.pusher.key') }}',
            pusherCluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
            environment: '{{ config('app.env') }}',
            enhancementLevel: {{ $enhancementLevel ?? 4 }},
            locale: '{{ app()->getLocale() }}',
            routes: {
                dashboard: '{{ route('portal.dashboard') }}',
                calls: '{{ route('portal.calls.index') }}',
                appointments: '{{ route('portal.appointments.index') }}',
                customers: '{{ route('portal.customers.index') }}',
                team: '{{ route('portal.team.index') }}',
                billing: '{{ route('portal.billing.index') }}',
                settings: '{{ route('portal.settings') }}',
                logout: '{{ route('portal.logout') }}'
            }
        };
        
        // Performance timing
        window.__PERFORMANCE_MARK__ = 'spa-init';
        window.performance?.mark(window.__PERFORMANCE_MARK__);
    </script>
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    
    <!-- Additional styles for SPA -->
    <style>
        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* SPA loading indicator */
        #spa-loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loader-content {
            text-align: center;
        }
        
        .loader-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3182ce;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Prevent FOUC */
        .no-js { display: none; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- SPA Loading State -->
    <div id="spa-loader">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <p class="text-gray-600">Lade Portal...</p>
        </div>
    </div>
    
    <!-- React Root -->
    <div id="root" class="no-js"></div>
    
    <!-- Noscript fallback -->
    <noscript>
        <div style="padding: 2rem; text-align: center; background: #fef3c7; border-bottom: 1px solid #f59e0b;">
            <h1 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">JavaScript erforderlich</h1>
            <p>Diese Anwendung benötigt JavaScript. Bitte aktivieren Sie JavaScript in Ihrem Browser.</p>
            <p style="margin-top: 1rem;">
                <a href="?enhancement_level=0" style="color: #3182ce; text-decoration: underline;">
                    Zur Basis-Version wechseln (ohne JavaScript)
                </a>
            </p>
        </div>
    </noscript>
    
    <!-- Error Boundary Fallback -->
    <div id="error-boundary" style="display: none; padding: 2rem; text-align: center;">
        <h1 style="font-size: 1.5rem; font-weight: bold; color: #dc2626; margin-bottom: 1rem;">
            Ein Fehler ist aufgetreten
        </h1>
        <p style="margin-bottom: 1rem;">Die Anwendung konnte nicht geladen werden.</p>
        <button onclick="window.location.reload()" 
                style="padding: 0.5rem 1rem; background: #3182ce; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">
            Seite neu laden
        </button>
        <p style="margin-top: 1rem;">
            <a href="?enhancement_level=2" style="color: #3182ce; text-decoration: underline;">
                Zur einfacheren Version wechseln
            </a>
        </p>
    </div>
    
    <!-- React and dependencies -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    
    <!-- Main application bundle -->
    <script src="{{ mix('js/app.js') }}" defer></script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('ServiceWorker registered:', registration);
                }).catch(error => {
                    console.log('ServiceWorker registration failed:', error);
                });
            });
        }
    </script>
    
    <!-- Performance Monitoring -->
    <script>
        // Remove loader when React is mounted
        window.addEventListener('react-mounted', () => {
            const loader = document.getElementById('spa-loader');
            if (loader) {
                loader.style.opacity = '0';
                loader.style.transition = 'opacity 0.3s';
                setTimeout(() => loader.remove(), 300);
            }
            
            // Show React root
            const root = document.getElementById('root');
            if (root) {
                root.classList.remove('no-js');
            }
            
            // Performance timing
            if (window.performance && window.performance.mark) {
                window.performance.mark('spa-interactive');
                window.performance.measure('spa-load-time', window.__PERFORMANCE_MARK__, 'spa-interactive');
                const measure = window.performance.getEntriesByName('spa-load-time')[0];
                console.log(`SPA loaded in ${Math.round(measure.duration)}ms`);
                
                // Send to analytics if available
                if (window.gtag) {
                    window.gtag('event', 'timing_complete', {
                        name: 'spa_load',
                        value: Math.round(measure.duration),
                        event_category: 'performance'
                    });
                }
            }
        });
        
        // Error boundary
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
            
            // Show error boundary if React fails to load
            if (!window.ReactAppMounted) {
                document.getElementById('spa-loader')?.remove();
                const errorBoundary = document.getElementById('error-boundary');
                if (errorBoundary) {
                    errorBoundary.style.display = 'block';
                }
            }
        });
        
        // Timeout fallback
        setTimeout(() => {
            if (!window.ReactAppMounted) {
                console.error('React app failed to mount within timeout');
                const loader = document.getElementById('spa-loader');
                if (loader && loader.parentNode) {
                    loader.innerHTML = `
                        <div class="loader-content">
                            <p class="text-red-600 mb-4">Ladevorgang dauert länger als erwartet...</p>
                            <button onclick="window.location.reload()" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Neu laden
                            </button>
                            <p class="mt-4">
                                <a href="?enhancement_level=2" class="text-blue-600 underline">
                                    Einfachere Version verwenden
                                </a>
                            </p>
                        </div>
                    `;
                }
            }
        }, 10000); // 10 second timeout
    </script>
    
    <!-- Analytics -->
    @if(config('app.env') === 'production')
        <!-- Google Analytics or similar -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.google.analytics_id') }}');
        </script>
    @endif
</body>
</html>