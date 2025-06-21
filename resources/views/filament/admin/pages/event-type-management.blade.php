<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Company Context Banner --}}
        <div class="bg-primary-50 dark:bg-primary-900/10 rounded-lg p-4 border border-primary-200 dark:border-primary-800">
            <div class="flex items-center gap-3">
                <x-heroicon-o-building-office class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                <div>
                    <h3 class="text-sm font-medium text-primary-900 dark:text-primary-100">
                        Verwaltung für: {{ $companyName }}
                    </h3>
                    <p class="text-xs text-primary-700 dark:text-primary-300 mt-1">
                        Alle Einstellungen gelten nur für dieses Unternehmen. Mitarbeiter können in anderen Unternehmen andere Event-Types haben.
                    </p>
                </div>
            </div>
        </div>

        {{-- Warnings Section --}}
        @if(count($warnings) > 0)
            <div class="space-y-3">
                @foreach($warnings as $warning)
                    <div class="bg-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-50 dark:bg-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-900/10 rounded-lg p-4 border border-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-200 dark:border-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-600 dark:text-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-400" />
                                <p class="text-sm text-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-900 dark:text-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-100">
                                    {{ $warning['message'] }}
                                </p>
                            </div>
                            <a href="{{ $warning['action'] }}" class="text-sm font-medium text-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-600 dark:text-{{ $warning['type'] === 'danger' ? 'danger' : ($warning['type'] === 'warning' ? 'warning' : 'info') }}-400 hover:underline">
                                {{ $warning['actionLabel'] }} →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Stats Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($stats as $stat)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ $stat['label'] }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                                {{ $stat['value'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $stat['description'] }}
                            </p>
                        </div>
                        <div class="p-3 bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/20 rounded-lg">
                            <x-dynamic-component :component="$stat['icon']" class="h-6 w-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Quick Actions --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Schnellzugriff
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->getQuickActions() as $action)
                    <a href="{{ $action['url'] }}" class="group relative bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/20 rounded-lg group-hover:bg-{{ $action['color'] }}-200 dark:group-hover:bg-{{ $action['color'] }}-900/30 transition-colors">
                                <x-dynamic-component :component="$action['icon']" class="h-5 w-5 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-{{ $action['color'] }}-600 dark:group-hover:text-{{ $action['color'] }}-400">
                                    {{ $action['label'] }}
                                </h3>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $action['description'] }}
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Recent Activities --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Letzte Zuordnungen
                </h2>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    @if($recentActivities->count() > 0)
                        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentActivities as $activity)
                                <li class="p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $activity->staff_name }}
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                zugeordnet zu: {{ $activity->event_type_name }}
                                            </p>
                                        </div>
                                        <time class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                                        </time>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="p-8 text-center">
                            <x-heroicon-o-clock class="h-8 w-8 text-gray-400 mx-auto" />
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                Noch keine Aktivitäten
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Help Section --}}
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Hilfe & Ressourcen
                </h2>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Multi-Company Setup
                            </h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                Mitarbeiter können für mehrere Unternehmen arbeiten. Jeder Mitarbeiter-Account ist einem Unternehmen zugeordnet und kann eigene Cal.com Verknüpfungen haben.
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                Wichtige Links
                            </h3>
                            <ul class="space-y-1">
                                <li>
                                    <a href="https://cal.com/docs/enterprise" target="_blank" class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1">
                                        <x-heroicon-o-arrow-top-right-on-square class="h-3 w-3" />
                                        Cal.com Enterprise Dokumentation
                                    </a>
                                </li>
                                <li>
                                    <a href="/help/videos/multi-company-setup" class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1">
                                        <x-heroicon-o-play-circle class="h-3 w-3" />
                                        Video: Multi-Company Setup
                                    </a>
                                </li>
                                <li>
                                    <a href="/docs/best-practices" class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1">
                                        <x-heroicon-o-document-text class="h-3 w-3" />
                                        Best Practices Guide
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>