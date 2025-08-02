<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Header --}}
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg shadow-lg p-6 text-white">
            <h2 class="text-2xl font-bold mb-2">{{ __('admin.messages.welcome') }}</h2>
            <p class="opacity-90">
                @if(auth()->check())
                    {{ auth()->user()->name ?? auth()->user()->email }} • {{ auth()->user()->company->name ?? 'System' }}
                @endif
            </p>
            <p class="text-sm opacity-75 mt-1">
                {{ now()->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}
            </p>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($stats as $stat)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stat['value']) }}</p>
                            @if(isset($stat['change']))
                                <p class="text-sm mt-2">
                                    <span class="text-{{ str_starts_with($stat['change'], '+') ? 'green' : 'red' }}-600 font-medium">
                                        {{ $stat['change'] }}
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400">vs. gestern</span>
                                </p>
                            @endif
                        </div>
                        <div class="p-3 bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/20 rounded-full">
                            <x-dynamic-component :component="$stat['icon']" class="w-6 h-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" />
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-10">
                        <x-dynamic-component :component="$stat['icon']" class="w-24 h-24 text-{{ $stat['color'] }}-600" />
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.quick_actions.title') }}</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($quickLinks as $link)
                        <a href="{{ $link['url'] }}" 
                           class="group relative bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition-all duration-200 hover:shadow-md">
                            <div class="flex items-start space-x-4">
                                <div class="p-2 bg-{{ $link['color'] }}-100 dark:bg-{{ $link['color'] }}-900/20 rounded-lg group-hover:scale-110 transition-transform">
                                    <x-dynamic-component :component="$link['icon']" class="w-5 h-5 text-{{ $link['color'] }}-600 dark:text-{{ $link['color'] }}-400" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 group-hover:text-{{ $link['color'] }}-600 dark:group-hover:text-{{ $link['color'] }}-400">
                                        {{ $link['label'] }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $link['description'] }}
                                    </p>
                                </div>
                                <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400 group-hover:text-{{ $link['color'] }}-600 dark:group-hover:text-{{ $link['color'] }}-400 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Recent Calls --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Letzte Anrufe</h3>
                    <a href="{{ route('filament.admin.resources.calls.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Alle anzeigen →
                    </a>
                </div>
                <div class="p-6">
                    @if($recentCalls->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('admin.messages.no_results') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach($recentCalls as $call)
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 bg-primary-100 dark:bg-primary-900/20 rounded-full">
                                        <x-heroicon-o-phone class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $call->customer->name ?? $call->from_number ?? 'Unbekannt' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $call->branch->name ?? 'Keine Filiale' }} • {{ $call->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full bg-{{ $call->status === 'completed' ? 'green' : 'gray' }}-100 dark:bg-{{ $call->status === 'completed' ? 'green' : 'gray' }}-900/20 text-{{ $call->status === 'completed' ? 'green' : 'gray' }}-600 dark:text-{{ $call->status === 'completed' ? 'green' : 'gray' }}-400">
                                        {{ $call->duration_sec }}s
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Appointments --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Neueste Termine</h3>
                    <a href="{{ route('filament.admin.resources.appointments.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Alle anzeigen →
                    </a>
                </div>
                <div class="p-6">
                    @if($recentAppointments->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('admin.messages.no_results') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach($recentAppointments as $appointment)
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 bg-success-100 dark:bg-success-900/20 rounded-full">
                                        <x-heroicon-o-calendar class="w-4 h-4 text-success-600 dark:text-success-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                            {{ $appointment->customer->name ?? 'Kunde' }} - {{ $appointment->service->name ?? 'Service' }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $appointment->branch->name ?? 'Keine Filiale' }} • {{ $appointment->date->format('d.m.Y H:i') }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-medium px-2 py-1 rounded-full bg-{{ $appointment->status === 'confirmed' ? 'green' : ($appointment->status === 'cancelled' ? 'red' : 'yellow') }}-100 dark:bg-{{ $appointment->status === 'confirmed' ? 'green' : ($appointment->status === 'cancelled' ? 'red' : 'yellow') }}-900/20 text-{{ $appointment->status === 'confirmed' ? 'green' : ($appointment->status === 'cancelled' ? 'red' : 'yellow') }}-600 dark:text-{{ $appointment->status === 'confirmed' ? 'green' : ($appointment->status === 'cancelled' ? 'red' : 'yellow') }}-400">
                                        {{ __('admin.status.' . $appointment->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- System Info (for debugging) --}}
        @if(config('app.debug'))
            <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Debug Info</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-gray-500">User ID:</span>
                        <span class="font-mono">{{ auth()->id() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Company ID:</span>
                        <span class="font-mono">{{ auth()->user()->company_id ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Session ID:</span>
                        <span class="font-mono">{{ substr(session()->getId(), 0, 8) }}...</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Route:</span>
                        <span class="font-mono">{{ request()->route()->getName() }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>