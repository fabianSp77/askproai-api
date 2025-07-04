// Pusher Integration for Live Dashboard Updates

window.initializePusher = function(companyId) {
    // Check if Pusher credentials are available
    if (!window.pusherKey) {
        console.log('Pusher not configured, falling back to polling');
        return;
    }

    // Initialize Pusher
    const pusher = new Pusher(window.pusherKey, {
        cluster: window.pusherCluster || 'mt1',
        encrypted: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }
    });

    // Subscribe to company channel
    const channel = pusher.subscribe(`private-company.${companyId}`);

    // Listen for call events
    channel.bind('App\\Events\\CallCreated', function(data) {
        console.log('New call created:', data);
        
        // Dispatch Livewire event to update widgets
        Livewire.dispatch('call-created', { call: data });
        
        // Show notification
        if (window.FilamentNotifications) {
            window.FilamentNotifications.notify({
                title: 'Neuer Anruf',
                body: `Eingehender Anruf von ${data.from_number}`,
                type: 'info',
                duration: 5000
            });
        }
    });

    channel.bind('App\\Events\\CallUpdated', function(data) {
        console.log('Call updated:', data);
        
        // Dispatch Livewire event to update widgets
        Livewire.dispatch('call-updated', { call: data });
    });

    channel.bind('App\\Events\\CallCompleted', function(data) {
        console.log('Call completed:', data);
        
        // Dispatch Livewire event to update widgets
        Livewire.dispatch('call-completed', { call: data });
        
        // Show notification
        if (window.FilamentNotifications) {
            window.FilamentNotifications.notify({
                title: 'Anruf beendet',
                body: `Anruf mit ${data.from_number} beendet (${data.duration}s)`,
                type: 'success',
                duration: 5000
            });
        }
    });

    // Connection status handlers
    pusher.connection.bind('connected', function() {
        console.log('Connected to Pusher');
        Livewire.dispatch('pusher-connected');
    });

    pusher.connection.bind('disconnected', function() {
        console.log('Disconnected from Pusher');
        Livewire.dispatch('pusher-disconnected');
    });

    pusher.connection.bind('error', function(err) {
        console.error('Pusher error:', err);
    });

    // Store pusher instance for cleanup
    window.pusherInstance = pusher;
};

// Cleanup function
window.cleanupPusher = function() {
    if (window.pusherInstance) {
        window.pusherInstance.disconnect();
        window.pusherInstance = null;
    }
};