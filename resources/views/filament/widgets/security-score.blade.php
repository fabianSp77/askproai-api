@php
    $securityData = $this->getSecurityScore();
    $score = $securityData['total'];
    $level = $securityData['level'];
    $achievements = $securityData['achievements'];
    $improvements = $securityData['improvements'];
    $tips = $securityData['tips'];
    $streak = $securityData['streak'];
@endphp

<x-filament-widgets::widget class="fi-security-score-widget">
    <x-filament::section>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        🛡️ Dein Sicherheits-Score
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Wie sicher ist dein Account?
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-{{ $level['color'] }}-600">
                        {{ $score }}/100
                    </div>
                    <div class="text-xs text-gray-500">
                        🔥 {{ $streak }} Tage Streak
                    </div>
                </div>
            </div>

            <!-- Level Badge -->
            <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-{{ $level['color'] }}-50 to-{{ $level['color'] }}-100 dark:from-{{ $level['color'] }}-900/20 dark:to-{{ $level['color'] }}-800/20 rounded-xl">
                <div class="text-4xl animate-bounce">{{ $level['emoji'] }}</div>
                <div class="flex-1">
                    <div class="font-bold text-{{ $level['color'] }}-800 dark:text-{{ $level['color'] }}-200">
                        {{ $level['name'] }}
                    </div>
                    <div class="text-sm text-{{ $level['color'] }}-700 dark:text-{{ $level['color'] }}-300">
                        {{ $level['description'] }}
                    </div>
                    @if($level['next'])
                        <div class="text-xs text-{{ $level['color'] }}-600 dark:text-{{ $level['color'] }}-400 mt-1">
                            Nächstes Level: {{ $level['next'] }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-300">Fortschritt</span>
                    <span class="font-semibold">{{ $score }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-{{ $level['color'] }}-500 to-{{ $level['color'] }}-600 h-3 rounded-full transition-all duration-1000 ease-out relative"
                         style="width: {{ $score }}%">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 animate-shimmer"></div>
                    </div>
                </div>
            </div>

            <!-- Achievements -->
            @if(count($achievements) > 0)
                <div class="space-y-3">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        🏆 Deine Erfolge
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($achievements as $achievement)
                            <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="text-2xl">{{ $achievement['emoji'] }}</div>
                                <div class="flex-1">
                                    <div class="font-medium text-green-800 dark:text-green-200">
                                        {{ $achievement['name'] }}
                                    </div>
                                    <div class="text-sm text-green-600 dark:text-green-400">
                                        +{{ $achievement['points'] }} Punkte
                                    </div>
                                </div>
                                <div class="text-green-600 dark:text-green-400">
                                    ✓
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Improvements -->
            @if(count($improvements) > 0)
                <div class="space-y-3">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        🚀 Verbesserungsmöglichkeiten
                    </h4>
                    <div class="space-y-3">
                        @foreach($improvements as $improvement)
                            <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 hover:shadow-md transition-shadow cursor-pointer"
                                 @if(isset($improvement['action'])) 
                                     onclick="window.open('{{ $improvement['action'] }}', '_blank')"
                                 @endif>
                                <div class="text-2xl">{{ $improvement['emoji'] }}</div>
                                <div class="flex-1">
                                    <div class="font-medium text-blue-800 dark:text-blue-200">
                                        {{ $improvement['name'] }}
                                    </div>
                                    <div class="text-sm text-blue-600 dark:text-blue-400">
                                        +{{ $improvement['points'] }} Punkte möglich
                                    </div>
                                    @if(isset($improvement['description']))
                                        <div class="text-xs text-blue-500 dark:text-blue-400 mt-1">
                                            {{ $improvement['description'] }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($improvement['priority'] === 'high')
                                        <span class="px-2 py-1 text-xs bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 rounded-full">
                                            Wichtig
                                        </span>
                                    @elseif($improvement['priority'] === 'medium')
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400 rounded-full">
                                            Empfohlen
                                        </span>
                                    @endif
                                    <div class="text-blue-600 dark:text-blue-400">
                                        →
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Security Tip -->
            <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border-l-4 border-purple-400">
                <div class="flex items-start gap-3">
                    <div class="text-xl">💡</div>
                    <div>
                        <div class="font-medium text-purple-800 dark:text-purple-200 mb-1">
                            Sicherheits-Tipp
                        </div>
                        @foreach($tips as $tip)
                            <div class="text-sm text-purple-700 dark:text-purple-300 flex items-center gap-2">
                                <span>{{ $tip['emoji'] }}</span>
                                <span>{{ $tip['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            @if(count($improvements) > 0)
                <div class="text-center pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button onclick="securityUX?.showSecurityImprovements()" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all transform hover:scale-105">
                        <span>🎯</span>
                        <span>Score verbessern</span>
                    </button>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<style>
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .fi-security-score-widget .grid-cols-2 {
        grid-template-columns: 1fr;
    }
}

/* Dark mode enhancements */
@media (prefers-color-scheme: dark) {
    .fi-security-score-widget {
        --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgb(0 0 0 / 0));
    }
}

/* Hover effects */
.fi-security-score-widget [onclick]:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Achievement celebration animation */
.fi-security-score-widget .achievement-new {
    animation: celebrate 0.6s ease-out;
}

@keyframes celebrate {
    0% { transform: scale(0) rotate(180deg); opacity: 0; }
    50% { transform: scale(1.2) rotate(10deg); opacity: 1; }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize security score widget interactions
    if (typeof securityUX !== 'undefined') {
        securityUX.initSecurityScoreWidget();
    }
    
    // Add achievement celebration for new achievements
    const achievements = document.querySelectorAll('.fi-security-score-widget .bg-green-50');
    achievements.forEach((achievement, index) => {
        setTimeout(() => {
            achievement.style.animation = 'celebrate 0.6s ease-out';
        }, index * 200);
    });
});
</script>