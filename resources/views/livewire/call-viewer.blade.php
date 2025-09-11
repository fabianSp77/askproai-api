<div>
    <div x-data="callViewer()" x-init="init()" class="min-h-screen relative overflow-hidden bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-gray-900 dark:via-slate-900 dark:to-indigo-950">
        {{-- Animated Background Effects --}}
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-500/5 to-transparent animate-gradient-shift"></div>
            <div class="absolute top-0 -left-40 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"></div>
            <div class="absolute top-20 -right-40 w-80 h-80 bg-yellow-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float-delayed"></div>
            <div class="absolute -bottom-40 left-20 w-80 h-80 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float-slow"></div>
        </div>
        
        {{-- Main Container --}}
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            {{-- Keyboard Shortcuts Indicator --}}
            <div class="fixed bottom-4 right-4 z-50">
                <div x-data="{ showShortcuts: false }" class="relative">
                    <button @click="showShortcuts = !showShortcuts" 
                            class="p-3 bg-gray-900 dark:bg-gray-800 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                            title="Keyboard Shortcuts">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                    </button>
                    
                    <div x-show="showShortcuts" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="showShortcuts = false"
                         class="absolute bottom-16 right-0 bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-4 w-64">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Keyboard Shortcuts</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">1-4</kbd>
                                <span class="text-gray-600 dark:text-gray-400">Switch Tabs</span>
                            </div>
                            <div class="flex justify-between">
                                <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">Ctrl+E</kbd>
                                <span class="text-gray-600 dark:text-gray-400">Export</span>
                            </div>
                            <div class="flex justify-between">
                                <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">Ctrl+R</kbd>
                                <span class="text-gray-600 dark:text-gray-400">Refresh</span>
                            </div>
                            <div class="flex justify-between">
                                <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">/</kbd>
                                <span class="text-gray-600 dark:text-gray-400">Search</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Premium Header with Glassmorphism --}}
            <div class="mb-8 relative group" x-show="showHeader" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-purple-600/20 rounded-3xl blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
                <div class="relative backdrop-blur-xl bg-white/70 dark:bg-gray-900/70 rounded-3xl border border-white/50 dark:border-gray-700/30 shadow-2xl overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-transparent to-purple-500/5"></div>
                    
                    <div class="relative p-8">
                        {{-- Header Top --}}
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                            <div class="mb-4 lg:mb-0">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-200 bg-clip-text text-transparent">
                                            Anrufdetails
                                        </h1>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($call->start_timestamp)->format('d. F Y, H:i') }} Uhr
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                        </svg>
                                        ID: {{ $call->call_id }}
                                    </span>
                                    
                                    @if($call->retell_call_id)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                        Retell: {{ substr($call->retell_call_id, 0, 8) }}...
                                    </span>
                                    @endif
                                    
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium relative overflow-hidden
                                        {{ $call->call_status === 'ended' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 
                                           ($call->call_status === 'error' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' : 
                                           'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300') }}">
                                        <span class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></span>
                                        <span class="relative flex items-center">
                                            <span class="w-2 h-2 rounded-full mr-1.5 animate-pulse
                                                {{ $call->call_status === 'ended' ? 'bg-green-500' : 
                                                   ($call->call_status === 'error' ? 'bg-red-500' : 'bg-yellow-500') }}">
                                            </span>
                                            {{ ucfirst($call->call_status ?? 'unknown') }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="flex items-center space-x-2">
                                {{-- Auto-refresh Toggle --}}
                                <button wire:click="toggleAutoRefresh" 
                                        class="group relative p-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                                        title="{{ $autoRefresh ? 'Stop Auto-refresh' : 'Start Auto-refresh' }}">
                                    <span class="absolute inset-0 bg-gradient-to-r from-green-400 to-green-600 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"></span>
                                    <svg class="relative w-5 h-5 {{ $autoRefresh ? 'text-green-500 animate-spin' : 'text-gray-600 dark:text-gray-400' }} group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                                
                                @if($call->recording_url)
                                <button onclick="document.getElementById('audio-section').scrollIntoView({behavior: 'smooth'})" 
                                        class="group relative p-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                                        title="Play Recording">
                                    <span class="absolute inset-0 bg-gradient-to-r from-blue-400 to-blue-600 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"></span>
                                    <svg class="relative w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                                    </svg>
                                </button>
                                @endif
                                
                                <button wire:click="$set('showExportModal', true)" 
                                        class="group relative p-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                                        title="Export (Ctrl+E)">
                                    <span class="absolute inset-0 bg-gradient-to-r from-purple-400 to-purple-600 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"></span>
                                    <svg class="relative w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </button>
                                
                                <button wire:click="printView" 
                                        class="group relative p-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                                        title="Print">
                                    <span class="absolute inset-0 bg-gradient-to-r from-gray-400 to-gray-600 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"></span>
                                    <svg class="relative w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </button>
                                
                                <button wire:click="shareCall" 
                                        class="group relative p-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
                                        title="Share">
                                    <span class="absolute inset-0 bg-gradient-to-r from-indigo-400 to-indigo-600 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"></span>
                                    <svg class="relative w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 01-7.432 0m9.032-4.026A9.001 9.001 0 0112 3c-4.474 0-8.268 3.12-9.032 7.326m0 0A9.001 9.001 0 0012 21c4.474 0 8.268-3.12 9.032-7.326"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        {{-- Animated Stats Cards --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="group relative overflow-hidden bg-gradient-to-br from-white/80 to-white/60 dark:from-gray-800/80 dark:to-gray-800/60 backdrop-blur-sm rounded-2xl p-5 border border-white/50 dark:border-gray-700/50 hover:shadow-xl transition-all duration-300" x-show="showStats" x-transition:enter="transition ease-out duration-500 delay-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dauer</p>
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="animatedDuration">{{ $formattedDuration }}</p>
                                    <div class="mt-2 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full animate-pulse" style="width: {{ min(100, ($call->duration_sec ?? 0) / 3) }}%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="group relative overflow-hidden bg-gradient-to-br from-white/80 to-white/60 dark:from-gray-800/80 dark:to-gray-800/60 backdrop-blur-sm rounded-2xl p-5 border border-white/50 dark:border-gray-700/50 hover:shadow-xl transition-all duration-300" x-show="showStats" x-transition:enter="transition ease-out duration-500 delay-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Antwortzeit</p>
                                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $callMetrics['response_time'] }}</p>
                                    <p class="mt-1 text-xs text-green-600 dark:text-green-400 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                                        </svg>
                                        Schnell
                                    </p>
                                </div>
                            </div>
                            
                            <div class="group relative overflow-hidden bg-gradient-to-br from-white/80 to-white/60 dark:from-gray-800/80 dark:to-gray-800/60 backdrop-blur-sm rounded-2xl p-5 border border-white/50 dark:border-gray-700/50 hover:shadow-xl transition-all duration-300" x-show="showStats" x-transition:enter="transition ease-out duration-500 delay-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Zufriedenheit</p>
                                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $callMetrics['customer_satisfaction'] }}</p>
                                    <div class="mt-2 flex space-x-1">
                                        @for($i = 0; $i < 5; $i++)
                                        <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            @if($i < (int)($callMetrics['customer_satisfaction']) / 20)
                                            <div class="h-full bg-gradient-to-r from-purple-400 to-purple-600 rounded-full animate-pulse"></div>
                                            @endif
                                        </div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            
                            <div class="group relative overflow-hidden bg-gradient-to-br from-white/80 to-white/60 dark:from-gray-800/80 dark:to-gray-800/60 backdrop-blur-sm rounded-2xl p-5 border border-white/50 dark:border-gray-700/50 hover:shadow-xl transition-all duration-300" x-show="showStats" x-transition:enter="transition ease-out duration-500 delay-400" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="relative">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Effizienz</p>
                                        <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $callMetrics['efficiency_score'] }}</p>
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $callMetrics['efficiency_score'] === 'Sehr gut' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                            {{ $callMetrics['efficiency_score'] === 'Sehr gut' ? 'Optimal' : 'Normal' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Enhanced Tab Navigation with Icons --}}
            <div class="mb-6 bg-white/70 dark:bg-gray-900/70 backdrop-blur-xl rounded-2xl p-2 shadow-lg border border-white/50 dark:border-gray-700/30">
                <div class="flex space-x-2" role="tablist">
                    @foreach(['overview' => ['Übersicht', 'M4 6h16M4 10h16M4 14h16M4 18h16'], 'timeline' => ['Zeitverlauf', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'], 'analysis' => ['Analyse', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'], 'transcript' => ['Transkript', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z']] as $tab => $info)
                    <button 
                        wire:click="switchTab('{{ $tab }}')" 
                        role="tab"
                        aria-selected="{{ $activeTab === $tab ? 'true' : 'false' }}"
                        aria-controls="{{ $tab }}-panel"
                        class="flex-1 group relative flex items-center justify-center space-x-2 py-3 px-4 rounded-xl font-medium transition-all duration-200 
                            {{ $activeTab === $tab 
                                ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg transform scale-105' 
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <svg class="w-5 h-5 {{ $activeTab === $tab ? 'animate-pulse' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info[1] }}"></path>
                        </svg>
                        <span>{{ $info[0] }}</span>
                        @if($activeTab === $tab)
                        <span class="absolute inset-x-0 -bottom-px h-px bg-gradient-to-r from-transparent via-blue-500 to-transparent"></span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
            
            {{-- Tab Content with Animation --}}
            <div class="backdrop-blur-xl bg-white/70 dark:bg-gray-900/70 rounded-3xl border border-white/50 dark:border-gray-700/30 shadow-2xl overflow-hidden">
                @if($activeTab === 'overview')
                    {{-- Enhanced Overview Tab --}}
                    <div class="p-8 space-y-8" id="overview-panel" role="tabpanel" x-show="true" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        
                        {{-- Caller Information Card --}}
                        <div class="group relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 to-purple-500/10 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 dark:border-gray-700/50">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anrufer Information</h3>
                                </div>
                                
                                @php $callerInfo = $this->getCallerInfo(); @endphp
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach([
                                        ['Name', $callerInfo['name'], 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                        ['Unternehmen', $callerInfo['company'] ?? 'N/A', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                                        ['Telefon', $callerInfo['phone'], 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                        ['E-Mail', $callerInfo['email'] ?? 'N/A', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z']
                                    ] as $field)
                                    @if($field[1] && $field[1] !== 'N/A')
                                    <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-colors">
                                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $field[2] }}"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $field[0] }}</p>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $field[1] }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        {{-- Call Details Timeline --}}
                        <div class="relative bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 dark:border-gray-700/50">
                            <div class="flex items-center mb-4">
                                <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Anrufdetails</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach([
                                    ['Startzeit', $call->start_timestamp ? \Carbon\Carbon::parse($call->start_timestamp)->format('d.m.Y H:i:s') : 'N/A', 'green'],
                                    ['Endzeit', $call->end_timestamp ? \Carbon\Carbon::parse($call->end_timestamp)->format('d.m.Y H:i:s') : 'N/A', 'red'],
                                    ['Von', $call->from_number ?: 'N/A', 'blue'],
                                    ['An', $call->to_number ?: 'N/A', 'indigo'],
                                    ['Grund', $this->getDisconnectReason(), 'purple'],
                                    ['Typ', $call->call_type ?? 'N/A', 'amber']
                                ] as $detail)
                                <div class="group relative overflow-hidden bg-gradient-to-br from-{{ $detail[2] }}-50/50 to-transparent dark:from-{{ $detail[2] }}-900/20 dark:to-transparent p-4 rounded-xl border border-{{ $detail[2] }}-200/50 dark:border-{{ $detail[2] }}-800/50 hover:shadow-lg transition-all duration-300">
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-{{ $detail[2] }}-500/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $detail[0] }}</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $detail[1] }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        
                        {{-- Insights Section --}}
                        @php $insights = $this->getCallInsights(); @endphp
                        @if(count($insights) > 0)
                        <div class="relative bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-6 border border-indigo-200/50 dark:border-indigo-800/50">
                            <div class="flex items-center mb-4">
                                <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Wichtige Hinweise</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($insights as $insight)
                                <div class="flex items-start space-x-3 p-3 bg-white/50 dark:bg-gray-800/50 rounded-xl">
                                    <div class="p-2 bg-{{ $insight['color'] }}-100 dark:bg-{{ $insight['color'] }}-900/30 rounded-lg">
                                        <svg class="w-4 h-4 text-{{ $insight['color'] }}-600 dark:text-{{ $insight['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium text-{{ $insight['color'] }}-700 dark:text-{{ $insight['color'] }}-300">{{ $insight['type'] }}</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $insight['message'] }}</p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    
                @elseif($activeTab === 'timeline')
                    {{-- Enhanced Timeline Tab --}}
                    <div class="p-8" id="timeline-panel" role="tabpanel" x-show="true" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Anrufverlauf
                        </h3>
                        
                        <div class="relative">
                            {{-- Timeline Line --}}
                            <div class="absolute left-9 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-500 via-purple-500 to-pink-500"></div>
                            
                            <div class="space-y-8">
                                @foreach($timelineEvents as $index => $event)
                                <div class="relative flex items-start group" x-show="true" x-transition:enter="transition ease-out duration-500 delay-{{ $index * 100 }}" x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                                    {{-- Event Icon --}}
                                    <div class="relative z-10 flex items-center justify-center w-18 h-18">
                                        <div class="absolute inset-0 bg-{{ $event['color'] }}-500 rounded-full opacity-20 animate-ping"></div>
                                        <div class="relative flex items-center justify-center w-14 h-14 bg-gradient-to-br from-{{ $event['color'] }}-400 to-{{ $event['color'] }}-600 rounded-full shadow-lg transform group-hover:scale-110 transition-transform duration-200">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($event['icon'] === 'phone-incoming')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                @elseif($event['icon'] === 'chat-bubble-left-right')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                @elseif($event['icon'] === 'user-circle')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                @endif
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    {{-- Event Content --}}
                                    <div class="ml-6 flex-1">
                                        <div class="relative group-hover:translate-x-2 transition-transform duration-200">
                                            <div class="absolute -inset-1 bg-gradient-to-r from-{{ $event['color'] }}-400 to-{{ $event['color'] }}-600 rounded-2xl blur opacity-25 group-hover:opacity-40 transition-opacity duration-200"></div>
                                            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $event['title'] }}</h4>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-{{ $event['color'] }}-100 dark:bg-{{ $event['color'] }}-900/30 text-{{ $event['color'] }}-700 dark:text-{{ $event['color'] }}-300">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        {{ $event['time'] }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $event['description'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                @elseif($activeTab === 'analysis')
                    {{-- Enhanced Analysis Tab --}}
                    <div class="p-8 space-y-8" id="analysis-panel" role="tabpanel" x-show="true" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        
                        {{-- Sentiment Analysis with Chart --}}
                        <div class="relative bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl p-6 border border-purple-200/50 dark:border-purple-800/50">
                            <div class="flex items-center mb-6">
                                <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Stimmungsanalyse</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Gesamtstimmung</span>
                                        <span class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                            {{ ucfirst($sentimentData['overall']) }}
                                        </span>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        @foreach(['positive' => 'green', 'neutral' => 'gray', 'negative' => 'red'] as $sentiment => $color)
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm font-medium text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ ucfirst($sentiment) }}</span>
                                                <span class="text-sm font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ $sentimentData['scores'][$sentiment] }}%</span>
                                            </div>
                                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-{{ $color }}-400 to-{{ $color }}-600 rounded-full transition-all duration-1000 ease-out" 
                                                     style="width: {{ $sentimentData['scores'][$sentiment] }}%"
                                                     x-show="true" 
                                                     x-transition:enter="transition-all ease-out duration-1000" 
                                                     x-transition:enter-start="width: 0%" 
                                                     x-transition:enter-end="width: {{ $sentimentData['scores'][$sentiment] }}%">
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    
                                    <div class="mt-4 flex items-center justify-between p-3 bg-white/50 dark:bg-gray-800/50 rounded-xl">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Konfidenz</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $sentimentData['confidence'] }}%</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-center">
                                    <div class="relative w-48 h-48">
                                        <svg class="w-48 h-48 transform -rotate-90">
                                            <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="none" class="text-gray-200 dark:text-gray-700"></circle>
                                            <circle cx="96" cy="96" r="88" stroke="url(#gradient)" stroke-width="12" fill="none" stroke-linecap="round" stroke-dasharray="{{ 552 * $sentimentData['scores']['positive'] / 100 }} 552"></circle>
                                            <defs>
                                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                    <stop offset="0%" stop-color="#10b981"></stop>
                                                    <stop offset="100%" stop-color="#059669"></stop>
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="text-center">
                                                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $sentimentData['scores']['positive'] }}%</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Positiv</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Analysis Summary --}}
                        @php $analysis = $this->getAnalysisData(); @endphp
                        @if(!empty($analysis))
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @if(isset($analysis['summary']))
                            <div class="relative bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200/50 dark:border-blue-800/50">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Zusammenfassung</h4>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $analysis['summary'] }}</p>
                            </div>
                            @endif
                            
                            @if(isset($analysis['customer_request']))
                            <div class="relative bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-6 border border-green-200/50 dark:border-green-800/50">
                                <div class="flex items-center mb-4">
                                    <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Kundenanliegen</h4>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ $analysis['customer_request'] }}</p>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    
                @elseif($activeTab === 'transcript')
                    {{-- Enhanced Transcript Tab --}}
                    <div class="p-8" id="transcript-panel" role="tabpanel" x-show="true" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-6 h-6 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Gesprächstranskript
                            </h3>
                            
                            @if($call->transcript)
                            <button wire:click="toggleTranscript" class="px-4 py-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-xl hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"></path>
                                </svg>
                            </button>
                            @endif
                        </div>
                        
                        @if($call->transcript)
                            @php $transcript = $this->getFormattedTranscript(); @endphp
                            @if(is_array($transcript))
                            <div class="space-y-4 max-h-[600px] overflow-y-auto pr-4 custom-scrollbar">
                                @foreach($transcript as $index => $entry)
                                <div class="flex space-x-4 group" x-show="true" x-transition:enter="transition ease-out duration-300 delay-{{ min($index * 50, 500) }}" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                                    <div class="flex-shrink-0">
                                        <div class="relative">
                                            <div class="absolute inset-0 bg-gradient-to-br {{ $entry['role'] === 'agent' ? 'from-blue-400 to-blue-600' : 'from-green-400 to-green-600' }} rounded-full opacity-20 group-hover:opacity-30 transition-opacity"></div>
                                            <div class="relative w-12 h-12 rounded-full flex items-center justify-center {{ $entry['role'] === 'agent' ? 'bg-gradient-to-br from-blue-500 to-blue-700' : 'bg-gradient-to-br from-green-500 to-green-700' }}">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    @if($entry['role'] === 'agent')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    @endif
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="relative group-hover:translate-x-1 transition-transform duration-200">
                                            <div class="absolute -inset-1 bg-gradient-to-r {{ $entry['role'] === 'agent' ? 'from-blue-400 to-blue-600' : 'from-green-400 to-green-600' }} rounded-2xl blur opacity-10 group-hover:opacity-20 transition-opacity"></div>
                                            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-200/50 dark:border-gray-700/50">
                                                <p class="text-xs font-semibold {{ $entry['role'] === 'agent' ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }} mb-2">
                                                    {{ ucfirst($entry['role']) }}
                                                </p>
                                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                                    {{ $entry['content'] ?? $entry['text'] ?? '' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-6">
                                <pre class="text-xs text-gray-700 dark:text-gray-300">{{ json_encode($transcript, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                        @else
                            <div class="text-center py-12">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">Kein Transkript verfügbar</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            
            {{-- Audio Player Section --}}
            @if($call->recording_url)
            <div id="audio-section" class="mt-8">
                @livewire('audio-waveform-player', [
                    'audioUrl' => $call->recording_url,
                    'duration' => $call->duration_sec ?? 0,
                    'callId' => $call->id
                ])
            </div>
            @endif
            
            {{-- Related Information Cards --}}
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Tenant & Customer Card --}}
                <div class="backdrop-blur-xl bg-white/70 dark:bg-gray-900/70 rounded-3xl border border-white/50 dark:border-gray-700/30 shadow-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-1">
                        <div class="bg-white dark:bg-gray-900 p-6 rounded-t-[calc(1.5rem-4px)]">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                Verknüpfungen
                            </h3>
                            
                            <div class="space-y-3">
                                @if($call->tenant)
                                <a href="{{ route('filament.admin.resources.tenants.view', $call->tenant) }}" 
                                   class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl hover:shadow-md transition-all duration-200 group">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Mandant</p>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $call->tenant->name }}</p>
                                    </div>
                                    <svg class="w-5 h-5 text-indigo-500 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                                @endif
                                
                                @if($call->customer)
                                <a href="{{ route('filament.admin.resources.customers.view', $call->customer) }}" 
                                   class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl hover:shadow-md transition-all duration-200 group">
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kunde</p>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $call->customer->name }}</p>
                                    </div>
                                    <svg class="w-5 h-5 text-green-500 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Appointment Card --}}
                @if($call->appointment)
                <div class="backdrop-blur-xl bg-white/70 dark:bg-gray-900/70 rounded-3xl border border-white/50 dark:border-gray-700/30 shadow-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-1">
                        <div class="bg-white dark:bg-gray-900 p-6 rounded-t-[calc(1.5rem-4px)]">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Termin
                            </h3>
                            
                            <a href="{{ route('filament.admin.resources.appointments.view', $call->appointment) }}" 
                               class="block p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-md transition-all duration-200 group">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                        {{ ucfirst($call->appointment->status) }}
                                    </span>
                                    <svg class="w-5 h-5 text-purple-500 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Termin-ID: <span class="font-mono font-semibold">#{{ $call->appointment->id }}</span>
                                </p>
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            
            {{-- Export Modal --}}
            @if($showExportModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showExportModal', false)"></div>
                    
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                        Export Call Data
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Choose your preferred export format:
                                        </p>
                                        <div class="mt-4 space-y-2">
                                            <button wire:click="exportCall('csv')" 
                                                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H4v10h12V5h-2a1 1 0 100-2 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-gray-900 dark:text-white font-medium">CSV</span>
                                                </div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Spreadsheet format</span>
                                            </button>
                                            
                                            <button wire:click="exportCall('pdf')" 
                                                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-gray-900 dark:text-white font-medium">PDF</span>
                                                </div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Printable document</span>
                                            </button>
                                            
                                            <button wire:click="exportCall('json')" 
                                                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    <span class="text-gray-900 dark:text-white font-medium">JSON</span>
                                                </div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Developer format</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button wire:click="$set('showExportModal', false)" 
                                    type="button" 
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
        </div>
    </div>
    
    @push('scripts')
    <script>
    function callViewer() {
        return {
            showHeader: false,
            showStats: false,
            animatedDuration: '0:00',
            
            init() {
                // Stagger animations
                setTimeout(() => this.showHeader = true, 100);
                setTimeout(() => this.showStats = true, 300);
                
                // Animate duration counter
                const duration = {{ $call->duration_sec ?? 0 }};
                this.animateCounter(0, duration, 1500, (val) => {
                    const mins = Math.floor(val / 60);
                    const secs = Math.floor(val % 60);
                    this.animatedDuration = `${mins}:${secs.toString().padStart(2, '0')}`;
                });
            },
            
            animateCounter(start, end, duration, callback) {
                const startTime = performance.now();
                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const value = start + (end - start) * this.easeOutQuart(progress);
                    callback(value);
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };
                requestAnimationFrame(animate);
            },
            
            easeOutQuart(t) {
                return 1 - Math.pow(1 - t, 4);
            }
        }
    }
    
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('play-audio', (event) => {
            const audio = new Audio(event.url);
            audio.play();
        });
        
        Livewire.on('pause-audio', () => {
            const audios = document.querySelectorAll('audio');
            audios.forEach(audio => audio.pause());
        });
        
        Livewire.on('copy-to-clipboard', (event) => {
            navigator.clipboard.writeText(event.text);
        });
        
        Livewire.on('focus-search', () => {
            const searchInput = document.getElementById('transcript-search');
            if (searchInput) {
                searchInput.focus();
            }
        });
        
        Livewire.on('print-page', () => {
            window.print();
        });
        
        Livewire.on('start-polling', (event) => {
            setInterval(() => {
                Livewire.dispatch('refreshCallData');
            }, event.interval);
        });
        
        Livewire.on('stop-polling', () => {
            // Clear all intervals
            const highestId = window.setTimeout(() => {}, 0);
            for (let i = highestId; i >= 0; i--) {
                window.clearTimeout(i);
            }
        });
        
        Livewire.on('call-data-refreshed', () => {
            // Show notification or update indicator
            console.log('Call data refreshed');
        });
    });
    </script>
    @endpush
    
    @push('styles')
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(1deg); }
            75% { transform: translateY(20px) rotate(-1deg); }
        }
        
        @keyframes float-delayed {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(20px) rotate(-1deg); }
            75% { transform: translateY(-20px) rotate(1deg); }
        }
        
        @keyframes float-slow {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(2deg); }
        }
        
        @keyframes gradient-shift {
            0%, 100% { transform: translateX(0%); }
            50% { transform: translateX(100%); }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float-delayed 6s ease-in-out infinite 2s; }
        .animate-float-slow { animation: float-slow 8s ease-in-out infinite; }
        .animate-gradient-shift { animation: gradient-shift 8s ease infinite; }
        .animate-shimmer { animation: shimmer 2s ease-out infinite; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { 
            background: linear-gradient(to bottom, #6366f1, #8b5cf6); 
            border-radius: 3px; 
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { 
            background: linear-gradient(to bottom, #4f46e5, #7c3aed); 
        }
    </style>
    @endpush
</div>