<!DOCTYPE html>
<html lang="de" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kundenportal') - {{ config('app.name') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        'primary-dark': '#5568d3',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Smooth transitions */
        * {
            transition-property: color, background-color, border-color;
            transition-duration: 150ms;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Focus styles for accessibility */
        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        textarea:focus-visible,
        select:focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
    </style>

    @yield('head')
</head>
<body class="h-full antialiased" x-data="app" x-init="init()">
    <div class="min-h-full">
        @if(!isset($hideNavigation) || !$hideNavigation)
            @include('customer-portal.layouts.navigation')
        @endif

        <main>
            @yield('content')
        </main>
    </div>

    <!-- Global Toast Notification -->
    <div x-show="toast.show"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-4 right-4 z-50 max-w-sm w-full">
        <div class="rounded-lg shadow-lg p-4"
             :class="{
                'bg-green-50 border border-green-200': toast.type === 'success',
                'bg-red-50 border border-red-200': toast.type === 'error',
                'bg-blue-50 border border-blue-200': toast.type === 'info',
                'bg-yellow-50 border border-yellow-200': toast.type === 'warning'
             }">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="text-xl"
                       :class="{
                        'fas fa-check-circle text-green-500': toast.type === 'success',
                        'fas fa-exclamation-circle text-red-500': toast.type === 'error',
                        'fas fa-info-circle text-blue-500': toast.type === 'info',
                        'fas fa-exclamation-triangle text-yellow-500': toast.type === 'warning'
                       }"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium"
                       :class="{
                        'text-green-800': toast.type === 'success',
                        'text-red-800': toast.type === 'error',
                        'text-blue-800': toast.type === 'info',
                        'text-yellow-800': toast.type === 'warning'
                       }"
                       x-text="toast.message"></p>
                </div>
                <button @click="toast.show = false" class="ml-3 flex-shrink-0">
                    <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global Alpine.js app state
        document.addEventListener('alpine:init', () => {
            Alpine.data('app', () => ({
                // Toast notification system
                toast: {
                    show: false,
                    type: 'info',
                    message: ''
                },

                // Authentication state
                auth: {
                    token: null,
                    user: null
                },

                // Initialize app
                init() {
                    this.initAuth();
                    this.initAxios();
                },

                // Initialize authentication
                initAuth() {
                    this.auth.token = localStorage.getItem('customer_token');
                    const userJson = localStorage.getItem('customer_user');
                    if (userJson) {
                        try {
                            this.auth.user = JSON.parse(userJson);
                        } catch (e) {
                            console.error('Failed to parse user data:', e);
                        }
                    }
                },

                // Initialize Axios with default config
                initAxios() {
                    axios.defaults.baseURL = '{{ url("/") }}';
                    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                    axios.defaults.headers.common['Accept'] = 'application/json';
                    axios.defaults.headers.common['Content-Type'] = 'application/json';

                    // Add CSRF token
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (csrfToken) {
                        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
                    }

                    // Add auth token if available
                    if (this.auth.token) {
                        axios.defaults.headers.common['Authorization'] = `Bearer ${this.auth.token}`;
                    }

                    // Handle 401 responses globally
                    axios.interceptors.response.use(
                        response => response,
                        error => {
                            if (error.response && error.response.status === 401) {
                                this.logout();
                                window.location.href = '/kundenportal/login';
                            }
                            return Promise.reject(error);
                        }
                    );
                },

                // Show toast notification
                showToast(message, type = 'info', duration = 5000) {
                    this.toast.message = message;
                    this.toast.type = type;
                    this.toast.show = true;

                    if (duration > 0) {
                        setTimeout(() => {
                            this.toast.show = false;
                        }, duration);
                    }
                },

                // Login with token
                login(token, user) {
                    this.auth.token = token;
                    this.auth.user = user;
                    localStorage.setItem('customer_token', token);
                    localStorage.setItem('customer_user', JSON.stringify(user));
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                },

                // Logout
                logout() {
                    this.auth.token = null;
                    this.auth.user = null;
                    localStorage.removeItem('customer_token');
                    localStorage.removeItem('customer_user');
                    delete axios.defaults.headers.common['Authorization'];
                },

                // Check if user is authenticated
                isAuthenticated() {
                    return !!this.auth.token;
                },

                // Format date
                formatDate(dateString, format = 'long') {
                    const date = new Date(dateString);

                    if (format === 'long') {
                        return date.toLocaleDateString('de-DE', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    } else if (format === 'short') {
                        return date.toLocaleDateString('de-DE');
                    } else if (format === 'time') {
                        return date.toLocaleTimeString('de-DE', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }

                    return date.toLocaleDateString('de-DE');
                },

                // Format time
                formatTime(timeString) {
                    if (!timeString) return '';
                    const [hours, minutes] = timeString.split(':');
                    return `${hours}:${minutes}`;
                },

                // Handle API errors
                handleApiError(error) {
                    console.error('API Error:', error);

                    if (error.response) {
                        // Server responded with error status
                        const message = error.response.data?.message ||
                                      error.response.data?.error ||
                                      'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
                        this.showToast(message, 'error');
                    } else if (error.request) {
                        // Request made but no response
                        this.showToast('Keine Verbindung zum Server. Bitte überprüfen Sie Ihre Internetverbindung.', 'error');
                    } else {
                        // Something else happened
                        this.showToast('Ein unerwarteter Fehler ist aufgetreten.', 'error');
                    }
                }
            }));
        });
    </script>

    @yield('scripts')
</body>
</html>
