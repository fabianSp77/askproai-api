<x-filament-widgets::widget>
    <div class="fi-wi-live-status-widget relative overflow-hidden">
        {{-- Live Status Bar --}}
        <div class="live-status-bar flex items-center justify-between p-4 rounded-lg" 
             :class="{ 'is-live': @js($isLive), 'is-idle': !@js($isLive) }">
            
            {{-- Left Side: Live Indicator --}}
            <div class="flex items-center gap-3">
                <div class="live-indicator relative">
                    <div class="live-dot" :class="{ 'pulse': @js($isLive) }"></div>
                    <div class="live-ring" :class="{ 'pulse': @js($isLive) }"></div>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        @if($isLive)
                            Live - Anrufe werden empfangen
                        @else
                            Bereit - Warte auf Anrufe
                        @endif
                    </h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        Automatische Aktualisierung alle 5 Sekunden
                    </p>
                </div>
            </div>
            
            {{-- Center: Quick Stats --}}
            <div class="flex items-center gap-6">
                @if($activeCalls > 0)
                    <div class="stat-item">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-phone-arrow-down-left class="w-4 h-4 text-green-600 animate-pulse" />
                            <span class="text-2xl font-bold text-green-600">{{ $activeCalls }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Aktive Anrufe</p>
                    </div>
                @endif
                
                <div class="stat-item">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-4 h-4 text-blue-600" />
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $recentCallsCount }}</span>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Letzte 5 Minuten</p>
                </div>
                
                @if($lastCallTime)
                    <div class="stat-item">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calendar class="w-4 h-4 text-gray-600" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $lastCallTime->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Letzter Anruf</p>
                    </div>
                @endif
            </div>
            
            {{-- Right Side: Last Update Time --}}
            <div class="text-right">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-arrow-path class="w-3 h-3 inline-block animate-spin" />
                    Aktualisiert: {{ $lastUpdate->format('H:i:s') }}
                </p>
            </div>
        </div>
        
        {{-- Progress Bar for Next Update --}}
        <div class="update-progress-bar"></div>
    </div>
    
    {{-- Notification Sound for New Calls (optional) --}}
    <audio id="notification-sound" preload="auto">
        <source src="/sounds/notification.mp3" type="audio/mpeg">
        <source src="/sounds/notification.ogg" type="audio/ogg">
    </audio>
    
    <style>
        .fi-wi-live-status-widget {
            margin-bottom: 1.5rem;
        }
        
        .live-status-bar {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border: 1px solid #e5e7eb;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .dark .live-status-bar {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            border-color: #374151;
        }
        
        .live-status-bar.is-live {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-color: #86efac;
        }
        
        .dark .live-status-bar.is-live {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            border-color: #10b981;
        }
        
        /* Live Indicator */
        .live-indicator {
            width: 12px;
            height: 12px;
            position: relative;
        }
        
        .live-dot {
            width: 12px;
            height: 12px;
            background-color: #6b7280;
            border-radius: 50%;
            position: absolute;
            top: 0;
            left: 0;
            transition: background-color 0.3s ease;
        }
        
        .live-dot.pulse {
            background-color: #10b981;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        
        .live-ring {
            width: 12px;
            height: 12px;
            border: 2px solid #6b7280;
            border-radius: 50%;
            position: absolute;
            top: -2px;
            left: -2px;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .live-ring.pulse {
            border-color: #10b981;
            animation: pulse-ring 2s ease-in-out infinite;
        }
        
        @keyframes pulse-dot {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }
        
        /* Stat Items */
        .stat-item {
            text-align: center;
            padding: 0 1rem;
            border-right: 1px solid #e5e7eb;
        }
        
        .dark .stat-item {
            border-color: #374151;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        /* Progress Bar */
        .update-progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            background-color: #3b82f6;
            width: 0;
            animation: progress 5s linear infinite;
        }
        
        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        
        /* Notification Animation */
        @keyframes notify-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .new-call-notification {
            animation: notify-bounce 0.5s ease-in-out 3;
        }
    </style>
    
    <script>
        // Track previous call count for notifications
        let previousCallCount = {{ $recentCallsCount }};
        
        // Listen for Livewire updates
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                // Check if this is our widget being updated
                if (component.fingerprint.name === 'filament.admin.widgets.call-live-status-widget') {
                    const currentCallCount = component.data.recentCallsCount;
                    
                    // If we have new calls, trigger notification
                    if (currentCallCount > previousCallCount) {
                        // Add notification animation
                        const widget = document.querySelector('.fi-wi-live-status-widget');
                        widget.classList.add('new-call-notification');
                        
                        // Remove animation class after it completes
                        setTimeout(() => {
                            widget.classList.remove('new-call-notification');
                        }, 1500);
                        
                        // Play notification sound (if enabled)
                        const sound = document.getElementById('notification-sound');
                        if (sound && localStorage.getItem('enableCallNotificationSound') === 'true') {
                            sound.play().catch(() => {
                                // Ignore errors (browser may block autoplay)
                            });
                        }
                        
                        // Show browser notification (if permitted)
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('Neuer Anruf eingegangen!', {
                                body: `${currentCallCount - previousCallCount} neue(r) Anruf(e) empfangen`,
                                icon: '/favicon.ico',
                                tag: 'new-call'
                            });
                        }
                    }
                    
                    previousCallCount = currentCallCount;
                }
            });
        });
        
        // Request notification permission on load
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
</x-filament-widgets::widget>