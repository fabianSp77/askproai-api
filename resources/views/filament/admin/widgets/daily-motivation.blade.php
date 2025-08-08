<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Header with animated greeting --}}
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="animate-bounce-once">
                    {{ $timeOfDay }}
                </div>
                <span class="gradient-text font-bold">
                    Hallo {{ $userName }}!
                </span>
                <div class="live-indicator ml-2"></div>
            </div>
        </x-slot>
        
        <x-slot name="description">
            <div class="text-sm text-gray-600 dark:text-gray-400 italic">
                {{ $motivation['timeGreeting'] }}
            </div>
        </x-slot>

        {{-- Main motivation content --}}
        <div class="space-y-6">
            {{-- Quick Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                {{-- Calls Today --}}
                <div class="glass-card p-4 text-center transition-smooth hover-lift">
                    <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ $stats['calls'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Anrufe heute
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2 dark:bg-gray-700">
                        <div class="bg-primary-500 h-2 rounded-full transition-all duration-1000 ease-out progress-ring" 
                             style="width: {{ $progressRing['calls'] }}%"></div>
                    </div>
                </div>

                {{-- Appointments Today --}}
                <div class="glass-card p-4 text-center transition-smooth hover-lift">
                    <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                        {{ $stats['appointments'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Termine gebucht
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2 dark:bg-gray-700">
                        <div class="bg-success-500 h-2 rounded-full transition-all duration-1000 ease-out" 
                             style="width: {{ $progressRing['appointments'] }}%"></div>
                    </div>
                </div>

                {{-- Success Rate --}}
                <div class="glass-card p-4 text-center transition-smooth hover-lift">
                    <div class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                        {{ $stats['success_rate'] }}%
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Erfolgsquote
                    </div>
                    <div class="text-xs mt-1 text-gray-500">
                        @if($stats['success_rate'] >= 25)
                            🏆 Weltklasse!
                        @elseif($stats['success_rate'] >= 15)
                            ⭐ Exzellent!
                        @elseif($stats['success_rate'] >= 10)
                            📈 Gut drauf!
                        @else
                            🎯 Am Start!
                        @endif
                    </div>
                </div>

                {{-- Total Talk Time --}}
                <div class="glass-card p-4 text-center transition-smooth hover-lift">
                    <div class="text-2xl font-bold text-info-600 dark:text-info-400">
                        {{ gmdate('i:s', $stats['total_duration']) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Gesprächszeit
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2 dark:bg-gray-700">
                        <div class="bg-info-500 h-2 rounded-full transition-all duration-1000 ease-out" 
                             style="width: {{ $progressRing['duration'] }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Performance Messages --}}
            @if($motivation['performance'])
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 
                           rounded-xl p-4 border-l-4 border-blue-500 transition-bounce hover:shadow-lg">
                    <div class="font-semibold text-blue-800 dark:text-blue-200">
                        {{ $motivation['performance'] }}
                    </div>
                </div>
            @endif

            @if(isset($motivation['success']))
                <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 
                           rounded-xl p-4 border-l-4 border-green-500 transition-bounce hover:shadow-lg">
                    <div class="font-semibold text-green-800 dark:text-green-200">
                        {{ $motivation['success'] }}
                    </div>
                </div>
            @endif

            {{-- Achievements Section --}}
            @if(!empty($achievements))
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-800/20 
                           rounded-xl p-6 border border-purple-200 dark:border-purple-700">
                    <h3 class="font-bold text-lg mb-4 text-purple-800 dark:text-purple-200">
                        🏆 Neue Erfolge freigeschaltet!
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($achievements as $achievement)
                            <div class="achievement-badge team-achievement">
                                <span class="text-lg mr-2">{{ $achievement['title'] }}</span>
                                <span class="text-sm opacity-90">{{ $achievement['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Daily Quote --}}
            <div class="text-center py-6">
                <blockquote class="text-lg font-medium text-gray-800 dark:text-gray-200 italic">
                    "{{ $motivation['quote'] }}"
                </blockquote>
                <div class="text-sm text-gray-500 mt-2">
                    — Dein Call Center Motto des Tages
                </div>
            </div>

            {{-- Team Stats (if available) --}}
            @if($teamStats['total_calls'] > 0)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4 text-center">
                        🤝 Team-Power heute
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                {{ $teamStats['total_calls'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Team-Anrufe
                            </div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-success-600 dark:text-success-400">
                                {{ $teamStats['total_appointments'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Team-Termine
                            </div>
                        </div>
                        <div class="md:col-span-1 col-span-2">
                            <div class="text-lg font-semibold text-warning-600 dark:text-warning-400">
                                {{ $teamStats['team_mood'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Team-Stimmung
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="flex flex-wrap gap-3 justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button 
                    color="success" 
                    size="sm"
                    icon="heroicon-o-phone"
                    href="{{ route('filament.admin.resources.calls.index') }}">
                    🎯 Zum Call Center
                </x-filament::button>

                <x-filament::button 
                    color="primary" 
                    size="sm"
                    icon="heroicon-o-calendar"
                    href="{{ route('filament.admin.resources.appointments.index') }}">
                    📅 Termine verwalten
                </x-filament::button>

                <x-filament::button 
                    color="warning" 
                    size="sm"
                    icon="heroicon-o-chart-bar"
                    onclick="window.showMotivationalMessage()">
                    ✨ Motivation tanken
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- Add some JavaScript magic --}}
    @script
    <script>
        // Show confetti if achievements are present
        @if(!empty($achievements))
            setTimeout(() => {
                // Trigger confetti animation
                if (window.ModernCallInteractions) {
                    const interactions = new window.ModernCallInteractions();
                    interactions.triggerConfettiExplosion();
                    if (interactions.soundEnabled) {
                        interactions.playAchievementSound();
                    }
                }
            }, 500);
        @endif

        // Make motivational message globally available
        window.showMotivationalMessage = function() {
            const quotes = {!! json_encode([
                'Du machst den Unterschied! Jeder Anruf zählt! 🌟',
                'Service-Champion am Werk! Weiter so! 💪', 
                'Deine Professionalität begeistert Kunden! ⭐',
                'Call Center Rockstar in Action! 🚀',
                'Deutsche Gründlichkeit + Herzlichkeit = Unschlagbar! 🇩🇪'
            ]) !!};
            
            const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
            
            // Show Filament notification if available
            if (typeof window.$wire !== 'undefined') {
                window.$wire.dispatch('notify', {
                    title: randomQuote,
                    type: 'success',
                    duration: 4000
                });
            } else {
                alert(randomQuote);
            }
        };

        // Animate progress bars on load
        window.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-ring');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        });

        // Add keyboard shortcut for motivation
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key === 'm') {
                e.preventDefault();
                window.showMotivationalMessage();
            }
        });
    </script>
    @endscript
</x-filament-widgets::widget>