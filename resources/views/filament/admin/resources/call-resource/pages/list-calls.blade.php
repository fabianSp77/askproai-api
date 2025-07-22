<x-filament-panels::page>
    {{-- Data is now provided by the controller --}}

    {{-- Static Widget Rendering --}}
    @if($company)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Anrufe heute --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Anrufe heute
                        </span>
                    </div>
                    
                    <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $todayCount }}
                    </div>
                    
                    <div class="flex items-center gap-x-1">
                        <svg class="fi-wi-stats-overview-stat-icon h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        <span class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400">
                            {{ $todayCount === 1 ? 'Anruf' : 'Anrufe' }}
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Diese Woche --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Diese Woche
                        </span>
                    </div>
                    
                    <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $weekCount }}
                    </div>
                    
                    <div class="flex items-center gap-x-1">
                        <svg class="fi-wi-stats-overview-stat-icon h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <span class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400">
                            {{ $weekCount === 1 ? 'Anruf empfangen' : 'Anrufe empfangen' }}
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Durchschnittsdauer --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Ø Gesprächsdauer
                        </span>
                    </div>
                    
                    <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $avgDurationFormatted }}
                    </div>
                    
                    <div class="flex items-center gap-x-1">
                        <svg class="fi-wi-stats-overview-stat-icon h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400">
                            Minuten:Sekunden
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Konversionsrate --}}
            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-y-2">
                    <div class="flex items-center gap-x-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Konversionsrate
                        </span>
                    </div>
                    
                    <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $conversionRate }}%
                    </div>
                    
                    <div class="flex items-center gap-x-1">
                        <svg class="fi-wi-stats-overview-stat-icon h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                        <span class="fi-wi-stats-overview-stat-description text-sm text-gray-500 dark:text-gray-400">
                            Termine aus Anrufen
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Debug Info --}}
    @if(config('app.debug'))
        <div style="background: #f3f4f6; padding: 1rem; margin-bottom: 1rem; border-radius: 0.5rem;">
            <p><strong>Debug Info:</strong></p>
            <p>Has Company: {{ $company ? 'Yes' : 'No' }}</p>
            <p>Auth User: {{ auth()->user() ? auth()->user()->email : 'Not authenticated' }}</p>
            <p>Company: {{ $company->name ?? 'No company' }} (ID: {{ $company->id ?? 'N/A' }})</p>
            <p>Static widgets rendered: Yes</p>
            <p>Today calls: {{ $todayCount ?? 0 }}</p>
            <p>Week calls: {{ $weekCount ?? 0 }}</p>
            <p>Total calls (month): {{ isset($conversionData) ? $conversionData->total : 0 }}</p>
            <p>Current time (Berlin): {{ now('Europe/Berlin')->format('Y-m-d H:i:s') }}</p>
            <p>Today date (Berlin): {{ today('Europe/Berlin')->format('Y-m-d') }}</p>
        </div>
    @endif

    {{-- Table --}}
    {{ $this->table }}
</x-filament-panels::page>