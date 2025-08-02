import Alpine from 'alpinejs';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Portal Store - Global state management for the business portal
export default function portalStore() {
    return {
        // Core state
        user: null,
        company: null,
        branches: [],
        currentBranch: null,
        staff: [],
        services: [],
        
        // UI state
        loading: false,
        sidebarOpen: true,
        notificationsOpen: false,
        notifications: [],
        unreadNotifications: 0,
        
        // Dashboard data
        stats: {
            calls_today: 0,
            appointments_today: 0,
            new_customers: 0,
            revenue_today: 0
        },
        
        // Settings
        theme: localStorage.getItem('theme') || 'light',
        locale: localStorage.getItem('locale') || 'de',
        
        // WebSocket
        echo: null,
        connected: false,
        
        // Initialize the store
        async init() {
            console.log('Portal Store initializing...');
            
            // Load persisted data
            this.loadPersistedData();
            
            // Setup axios defaults
            this.setupAxios();
            
            // Initialize WebSocket
            this.initializeWebSocket();
            
            // Load initial data
            await this.loadInitialData();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Watch for changes
            this.setupWatchers();
            
            console.log('Portal Store initialized');
        },
        
        // Load data from localStorage
        loadPersistedData() {
            const persisted = localStorage.getItem('portalStore');
            if (persisted) {
                try {
                    const data = JSON.parse(persisted);
                    if (data.currentBranch) {
                        this.currentBranch = data.currentBranch;
                    }
                    if (data.sidebarOpen !== undefined) {
                        this.sidebarOpen = data.sidebarOpen;
                    }
                } catch (e) {
                    console.error('Error loading persisted data:', e);
                }
            }
        },
        
        // Setup axios defaults
        setupAxios() {
            // Set base URL
            axios.defaults.baseURL = '/business/api';
            
            // Add auth token to all requests
            const token = document.querySelector('meta[name="api-token"]')?.content;
            if (token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            }
            
            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
            }
            
            // Add request interceptor
            axios.interceptors.request.use(
                config => {
                    this.loading = true;
                    return config;
                },
                error => {
                    this.loading = false;
                    return Promise.reject(error);
                }
            );
            
            // Add response interceptor
            axios.interceptors.response.use(
                response => {
                    this.loading = false;
                    return response;
                },
                error => {
                    this.loading = false;
                    this.handleError(error);
                    return Promise.reject(error);
                }
            );
        },
        
        // Initialize WebSocket connection
        initializeWebSocket() {
            const token = document.querySelector('meta[name="api-token"]')?.content;
            
            this.echo = new Echo({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY || 'askproai-websocket-key',
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
                wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
                wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
                wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
                forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
                encrypted: true,
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                auth: {
                    headers: {
                        Authorization: token ? `Bearer ${token}` : undefined
                    }
                },
                authEndpoint: '/broadcasting/auth'
            });
            
            // Monitor connection status
            this.echo.connector.pusher.connection.bind('state_change', states => {
                this.connected = states.current === 'connected';
            });
            
            // Subscribe to company channel
            if (this.company?.id) {
                this.subscribeToChannels();
            }
        },
        
        // Subscribe to WebSocket channels
        subscribeToChannels() {
            if (!this.company?.id) return;
            
            // Company channel
            this.echo.private(`company.${this.company.id}`)
                .listen('.call.status.updated', (e) => {
                    this.handleCallUpdate(e);
                })
                .listen('.appointment.updated', (e) => {
                    this.handleAppointmentUpdate(e);
                })
                .listen('.dashboard.stats.updated', (e) => {
                    this.stats = { ...this.stats, ...e.stats };
                });
            
            // User notifications channel
            if (this.user?.id) {
                this.echo.private(`user.${this.user.id}.notifications`)
                    .listen('.notification.created', (e) => {
                        this.addNotification(e);
                    });
            }
            
            // Presence channel
            this.echo.join(`presence.company.${this.company.id}`)
                .here((users) => {
                    console.log('Online users:', users);
                })
                .joining((user) => {
                    console.log('User joined:', user);
                })
                .leaving((user) => {
                    console.log('User left:', user);
                });
        },
        
        // Load initial data
        async loadInitialData() {
            try {
                // Load user data
                const userResponse = await axios.get('/user');
                this.user = userResponse.data.user;
                this.company = userResponse.data.company;
                
                // Load branches
                if (this.company) {
                    await this.loadBranches();
                    await this.loadDashboardStats();
                    await this.loadNotifications();
                    
                    // Subscribe to channels after loading company
                    this.subscribeToChannels();
                }
            } catch (error) {
                console.error('Error loading initial data:', error);
            }
        },
        
        // Load branches
        async loadBranches() {
            try {
                const response = await axios.get('/branches');
                this.branches = response.data.branches;
                
                // Set current branch if not set
                if (!this.currentBranch && this.branches.length > 0) {
                    this.currentBranch = this.branches[0];
                }
            } catch (error) {
                console.error('Error loading branches:', error);
            }
        },
        
        // Load dashboard stats
        async loadDashboardStats() {
            try {
                const response = await axios.get('/dashboard');
                if (response.data.stats) {
                    this.stats = response.data.stats;
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        },
        
        // Load notifications
        async loadNotifications() {
            try {
                const response = await axios.get('/notifications');
                this.notifications = response.data.notifications || [];
                this.unreadNotifications = this.notifications.filter(n => !n.read).length;
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        },
        
        // Add notification
        addNotification(notification) {
            this.notifications.unshift(notification);
            if (!notification.read) {
                this.unreadNotifications++;
            }
            
            // Show toast notification
            this.showToast(notification.title, notification.message, notification.type);
        },
        
        // Mark notification as read
        async markNotificationAsRead(notificationId) {
            const notification = this.notifications.find(n => n.id === notificationId);
            if (!notification || notification.read) return;
            
            try {
                await axios.post(`/notifications/${notificationId}/read`);
                notification.read = true;
                this.unreadNotifications--;
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },
        
        // Mark all notifications as read
        async markAllNotificationsAsRead() {
            try {
                await axios.post('/notifications/read-all');
                this.notifications.forEach(n => n.read = true);
                this.unreadNotifications = 0;
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
            }
        },
        
        // Switch branch
        async switchBranch(branchId) {
            const branch = this.branches.find(b => b.id === branchId);
            if (!branch) return;
            
            this.currentBranch = branch;
            
            // Reload dashboard data for new branch
            await this.loadDashboardStats();
            
            // Emit event for other components
            window.dispatchEvent(new CustomEvent('branch-switched', { detail: branch }));
        },
        
        // Toggle sidebar
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        // Toggle notifications
        toggleNotifications() {
            this.notificationsOpen = !this.notificationsOpen;
        },
        
        // Change theme
        changeTheme(theme) {
            this.theme = theme;
            localStorage.setItem('theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
        },
        
        // Change locale
        changeLocale(locale) {
            this.locale = locale;
            localStorage.setItem('locale', locale);
            // Reload page to apply new locale
            window.location.reload();
        },
        
        // API helper methods
        async get(url, params = {}) {
            return axios.get(url, { params });
        },
        
        async post(url, data = {}) {
            return axios.post(url, data);
        },
        
        async put(url, data = {}) {
            return axios.put(url, data);
        },
        
        async delete(url) {
            return axios.delete(url);
        },
        
        // Error handling
        handleError(error) {
            if (error.response?.status === 401) {
                // Unauthorized - redirect to login
                window.location.href = '/business/login';
            } else if (error.response?.status === 403) {
                this.showToast('Zugriff verweigert', 'Sie haben keine Berechtigung für diese Aktion.', 'error');
            } else if (error.response?.status === 404) {
                this.showToast('Nicht gefunden', 'Die angeforderte Ressource wurde nicht gefunden.', 'error');
            } else if (error.response?.status === 422) {
                // Validation error
                const message = error.response.data.message || 'Validierungsfehler';
                this.showToast('Validierungsfehler', message, 'error');
            } else if (error.response?.status >= 500) {
                this.showToast('Serverfehler', 'Ein unerwarteter Fehler ist aufgetreten.', 'error');
            }
        },
        
        // Show toast notification
        showToast(title, message, type = 'info') {
            // Emit custom event for toast component
            window.dispatchEvent(new CustomEvent('show-toast', {
                detail: { title, message, type }
            }));
        },
        
        // Handle real-time updates
        handleCallUpdate(data) {
            // Update stats if needed
            if (data.stats) {
                this.stats = { ...this.stats, ...data.stats };
            }
            
            // Emit event for other components
            window.dispatchEvent(new CustomEvent('call-updated', { detail: data }));
        },
        
        handleAppointmentUpdate(data) {
            // Update stats if needed
            if (data.stats) {
                this.stats = { ...this.stats, ...data.stats };
            }
            
            // Emit event for other components
            window.dispatchEvent(new CustomEvent('appointment-updated', { detail: data }));
        },
        
        // Setup event listeners
        setupEventListeners() {
            // Listen for auth changes
            window.addEventListener('auth-changed', (e) => {
                if (e.detail.authenticated) {
                    this.loadInitialData();
                } else {
                    this.reset();
                }
            });
        },
        
        // Setup watchers
        setupWatchers() {
            // Watch for changes and persist
            Alpine.effect(() => {
                const dataToStore = {
                    currentBranch: this.currentBranch,
                    sidebarOpen: this.sidebarOpen
                };
                localStorage.setItem('portalStore', JSON.stringify(dataToStore));
            });
        },
        
        // Reset store
        reset() {
            this.user = null;
            this.company = null;
            this.branches = [];
            this.currentBranch = null;
            this.staff = [];
            this.services = [];
            this.notifications = [];
            this.unreadNotifications = 0;
            this.stats = {
                calls_today: 0,
                appointments_today: 0,
                new_customers: 0,
                revenue_today: 0
            };
            
            // Disconnect WebSocket
            if (this.echo) {
                this.echo.disconnect();
            }
        },
        
        // Logout
        async logout() {
            try {
                await axios.post('/logout');
                this.reset();
                window.location.href = '/business/login';
            } catch (error) {
                console.error('Error during logout:', error);
            }
        }
    };
}