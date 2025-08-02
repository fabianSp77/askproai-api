<x-filament-panels::page>
    {{-- Force load the modern CSS file --}}
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/retell-modern-inline.css') }}?v={{ time() }}">
    @endpush
    
    {{-- Simple inline styles without external JavaScript --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('✅ Retell Dashboard loaded successfully');
                
                // Watch for tab changes
                document.addEventListener('click', function(e) {
                    if (e.target && e.target.textContent && e.target.textContent.includes('Functions')) {
                        setTimeout(function() {
                            console.log('Functions tab clicked - styles should be visible via CSS');
                        }, 100);
                    }
                });
                
                // Simple style check
                window.checkModernStyles = function() {
                    const cards = document.querySelectorAll('.function-card-modern');
                    console.log('Found ' + cards.length + ' function cards');
                    return cards.length;
                };
            });
        </script>
    @endpush
    
    <style>
        /* Retell Ultimate Dashboard - Modern UI/UX - High Specificity Override */
        .fi-page .retell-ultimate-dashboard {
            /* Container for scoped styles */
        }
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-medium: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Glassmorphism Card - Ultra High Specificity */
        .retell-ultimate-dashboard .glass-card,
        .retell-ultimate-dashboard .bg-white.rounded-xl {
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: var(--shadow-medium) !important;
            transition: all 0.3s ease !important;
        }
        
        .dark .retell-ultimate-dashboard .glass-card,
        .dark .retell-ultimate-dashboard .bg-white.rounded-xl {
            background: rgba(30, 41, 59, 0.5) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-large);
            border-color: rgba(102, 126, 234, 0.3);
        }

        /* Modern Function Card */
        .retell-ultimate-dashboard .function-card,
        .retell-ultimate-dashboard [wire\:click*="selectAgent"] {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 16px !important;
            padding: 24px !important;
            position: relative !important;
            overflow: hidden !important;
            transition: all 0.3s ease !important;
        }
        
        .dark .function-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(30, 41, 59, 0.6) 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }

        .function-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-large);
        }

        /* Modern Buttons */
        .btn-modern {
            position: relative !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            overflow: hidden !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        /* Function Type Badges */
        .function-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .function-badge.cal {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .function-badge.custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .function-badge.system {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #744210;
        }

        /* Parameter Cards */
        .parameter-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .parameter-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(102, 126, 234, 0.3);
        }

        /* Code Editor Styles */
        .code-editor {
            background: #1e1e1e;
            border-radius: 12px;
            padding: 20px;
            font-family: 'Fira Code', 'Monaco', 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.6;
            color: #d4d4d4;
            overflow-x: auto;
        }

        /* Input Modern */
        .input-modern {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .input-modern:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Animated Icons */
        .icon-pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        /* Function Card Modern - Force Styles */
        .function-card-modern {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(99, 102, 241, 0.3) !important;
            border-radius: 16px !important;
            padding: 24px !important;
            position: relative !important;
            overflow: hidden !important;
            transition: all 0.3s ease !important;
            margin-bottom: 16px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }
        
        .function-card-modern:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border-color: rgba(99, 102, 241, 0.5) !important;
        }
        
        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s ease !important;
        }
        
        /* Gradient Primary Button */
        .btn-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            border: none !important;
            cursor: pointer !important;
        }
        
        .btn-gradient-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.5) !important;
        }
        
        /* Parameter Card */
        .parameter-card {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 12px !important;
            padding: 16px !important;
            margin-bottom: 12px !important;
            transition: all 0.3s ease !important;
        }
        
        .parameter-card:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(102, 126, 234, 0.3) !important;
        }
        
        /* Function Badges */
        .function-badge {
            display: inline-flex !important;
            align-items: center !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
        }
        
        .function-badge.cal {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
        }
        
        .function-badge.custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
            color: white !important;
        }
        
        .function-badge.system {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%) !important;
            color: #744210 !important;
        }
    </style>
    
    <script>
        // Enhanced modern styles application with debugging
        function applyModernStyles() {
            console.log('[Retell Ultimate] Applying modern styles...');
            
            // Apply glassmorphism to all function cards
            const functionCards = document.querySelectorAll('.function-card-modern');
            console.log('[Retell Ultimate] Found', functionCards.length, 'function cards');
            functionCards.forEach((card, index) => {
                card.style.cssText = 'background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%) !important; backdrop-filter: blur(10px) !important; -webkit-backdrop-filter: blur(10px) !important; border: 1px solid rgba(99, 102, 241, 0.3) !important; border-radius: 16px !important; padding: 24px !important; position: relative !important; overflow: hidden !important; transition: all 0.3s ease !important; margin-bottom: 16px !important; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;';
                console.log('[Retell Ultimate] Styled card', index + 1);
            });
            
            // Apply gradient to badges
            const badges = document.querySelectorAll('.function-badge');
            console.log('[Retell Ultimate] Found', badges.length, 'badges');
            badges.forEach(badge => {
                if (badge.classList.contains('cal')) {
                    badge.style.cssText = 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 12px !important; font-weight: 600 !important; display: inline-flex !important;';
                } else if (badge.classList.contains('custom')) {
                    badge.style.cssText = 'background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important; color: white !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 12px !important; font-weight: 600 !important; display: inline-flex !important;';
                }
            });
            
            // Apply modern button styles
            const buttons = document.querySelectorAll('.btn-gradient-primary');
            console.log('[Retell Ultimate] Found', buttons.length, 'gradient buttons');
            buttons.forEach(btn => {
                btn.style.cssText = 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important; padding: 12px 24px !important; border-radius: 12px !important; font-weight: 600 !important; transition: all 0.3s ease !important; display: inline-flex !important; align-items: center !important; border: none !important; cursor: pointer !important;';
            });
            
            // Apply glass-card styles
            const glassCards = document.querySelectorAll('.glass-card');
            console.log('[Retell Ultimate] Found', glassCards.length, 'glass cards');
            glassCards.forEach(card => {
                card.style.cssText = 'background: rgba(255, 255, 255, 0.05) !important; backdrop-filter: blur(10px) !important; -webkit-backdrop-filter: blur(10px) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important; transition: all 0.3s ease !important;';
            });
            
            // Apply input-modern styles
            const modernInputs = document.querySelectorAll('.input-modern');
            console.log('[Retell Ultimate] Found', modernInputs.length, 'modern inputs');
            modernInputs.forEach(input => {
                input.style.cssText += 'background: rgba(255, 255, 255, 0.05) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; border-radius: 12px !important; padding: 12px 16px !important; transition: all 0.3s ease !important; width: 100% !important;';
            });
            
            // Apply parameter-card styles
            const paramCards = document.querySelectorAll('.parameter-card');
            console.log('[Retell Ultimate] Found', paramCards.length, 'parameter cards');
            paramCards.forEach(card => {
                card.style.cssText = 'background: rgba(255, 255, 255, 0.05) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; border-radius: 12px !important; padding: 16px !important; margin-bottom: 12px !important; transition: all 0.3s ease !important;';
            });
        }
        
        // Apply styles on DOM load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Retell Ultimate] DOM loaded, applying initial styles');
            applyModernStyles();
            
            // Watch for tab changes using MutationObserver
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        // Check if Functions tab is now visible
                        const functionsTab = document.querySelector('[x-show="selectedTab === \'functions\'"]');
                        if (functionsTab && functionsTab.style.display !== 'none') {
                            console.log('[Retell Ultimate] Functions tab shown, applying styles');
                            setTimeout(applyModernStyles, 100);
                        }
                    }
                });
            });
            
            // Start observing
            const targetNode = document.querySelector('.retell-ultimate-dashboard');
            if (targetNode) {
                observer.observe(targetNode, { 
                    attributes: true, 
                    subtree: true,
                    attributeFilter: ['style']
                });
            }
        });
        
        // Re-apply styles after Livewire updates
        window.addEventListener('livewire:load', function () {
            console.log('[Retell Ultimate] Livewire loaded');
            Livewire.hook('message.processed', (message, component) => {
                console.log('[Retell Ultimate] Livewire message processed, re-applying styles');
                setTimeout(applyModernStyles, 100);
            });
        });
        
        // Apply styles when Alpine.js updates
        document.addEventListener('alpine:init', () => {
            console.log('[Retell Ultimate] Alpine initialized');
            Alpine.data('retellStyles', () => ({
                init() {
                    this.$watch('selectedTab', (value) => {
                        if (value === 'functions') {
                            console.log('[Retell Ultimate] Functions tab selected via Alpine');
                            setTimeout(applyModernStyles, 100);
                        }
                    });
                }
            }));
        });
        
        // Force style application every 2 seconds as fallback
        setInterval(() => {
            const functionsTab = document.querySelector('[x-show="selectedTab === \'functions\'"]');
            if (functionsTab && functionsTab.style.display !== 'none') {
                const functionCards = document.querySelectorAll('.function-card-modern');
                if (functionCards.length > 0) {
                    const firstCard = functionCards[0];
                    // Check if styles are applied
                    if (!firstCard.style.background || !firstCard.style.background.includes('gradient')) {
                        console.log('[Retell Ultimate] Styles missing, re-applying...');
                        applyModernStyles();
                    }
                }
            }
        }, 2000);
    </script>
    <div class="retell-ultimate-dashboard space-y-6" x-data="{ 
        selectedTab: 'overview',
        expandedFunctions: [],
        showJsonView: false,
        searchTerm: '',
        ...retellStyles()
    }">
        @if($error)
            <div class="bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/20 rounded-lg p-4">
                <p class="text-danger-800 dark:text-danger-400">{{ $error }}</p>
            </div>
        @endif
        
        @if($successMessage)
            <div class="bg-success-50 dark:bg-success-500/10 border border-success-200 dark:border-success-500/20 rounded-lg p-4">
                <p class="text-success-800 dark:text-success-400">{{ $successMessage }}</p>
            </div>
        @endif

        {{-- Header with Agent Selector --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Select Agent to Configure</h2>
                <button wire:click="refreshData" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
            
            {{-- Search Bar --}}
            <div class="mb-4">
                <input 
                    type="text" 
                    placeholder="Search agents..." 
                    x-model="searchTerm"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-primary-500"
                >
            </div>
            
            {{-- Agent List with scrollable area --}}
            <div class="max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                <div class="space-y-3">
                    @php
                        $groupedAgents = collect($agents)->groupBy(function($agent) {
                            // Extract base name
                            $name = $agent['agent_name'];
                            if (preg_match('/^(.+?)[\s\/]+V\d+$/i', $name, $matches)) {
                                return trim($matches[1]);
                            }
                            return $name;
                        });
                    @endphp
                    
                    @foreach($groupedAgents as $baseName => $agentGroup)
                        <div 
                            x-show="!searchTerm || '{{ strtolower($baseName) }}'.includes(searchTerm.toLowerCase())"
                            x-transition
                            class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900 dark:text-white text-base">{{ $baseName }}</h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($agentGroup) }} versions</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                @foreach($agentGroup->sortByDesc(function($agent) {
                                    preg_match('/V(\d+)$/i', $agent['agent_name'], $matches);
                                    return isset($matches[1]) ? intval($matches[1]) : 0;
                                }) as $agent)
                                    @php
                                        $version = 'Default';
                                        if (preg_match('/V(\d+)$/i', $agent['agent_name'], $matches)) {
                                            $version = 'V' . $matches[1];
                                        }
                                    @endphp
                                    <button 
                                        wire:click="selectAgent('{{ $agent['agent_id'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        class="relative group p-3 border-2 rounded-lg transition-all text-center 
                                               {{ $selectedAgent && $selectedAgent['agent_id'] === $agent['agent_id'] 
                                                  ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-md transform scale-105' 
                                                  : 'border-gray-200 dark:border-gray-700 hover:border-primary-300 hover:shadow-sm' }}"
                                    >
                                        <div class="font-bold text-lg text-gray-900 dark:text-white">
                                            {{ $version }}
                                        </div>
                                        @if(isset($agent['response_engine']['type']) && $agent['response_engine']['type'] === 'retell-llm')
                                            <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                        @endif
                                        
                                        {{-- Loading indicator --}}
                                        <div wire:loading wire:target="selectAgent('{{ $agent['agent_id'] }}')" class="absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-800/80 rounded-lg">
                                            <svg class="animate-spin h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Total: {{ count($agents) }} agents
            </div>
        </div>

        @if($selectedAgent && $llmData)
            {{-- Tab Navigation --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button 
                            @click="selectedTab = 'overview'"
                            :class="selectedTab === 'overview' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        >
                            Overview
                        </button>
                        <button 
                            @click="selectedTab = 'prompt'"
                            :class="selectedTab === 'prompt' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        >
                            Prompt Editor
                        </button>
                        <button 
                            @click="selectedTab = 'functions'"
                            :class="selectedTab === 'functions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        >
                            Custom Functions ({{ count($llmData['general_tools'] ?? []) }})
                        </button>
                        <button 
                            @click="selectedTab = 'testing'"
                            :class="selectedTab === 'testing' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        >
                            Test Console
                        </button>
                        <button 
                            @click="selectedTab = 'settings'"
                            :class="selectedTab === 'settings' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        >
                            Agent Settings
                        </button>
                        <button 
                            @click="selectedTab = 'phone'"
                            :class="selectedTab === 'phone' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        >
                            Phone Numbers
                        </button>
                        <button 
                            @click="selectedTab = 'webhook'"
                            :class="selectedTab === 'webhook' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        >
                            Webhooks
                        </button>
                    </nav>
                </div>

                {{-- Tab Content --}}
                <div class="p-6">
                    {{-- Overview Tab --}}
                    <div x-show="selectedTab === 'overview'" x-transition>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">LLM Configuration</h3>
                                    <button 
                                        wire:click="startEditingLLMSettings"
                                        class="text-sm px-3 py-1 bg-primary-600 text-white rounded hover:bg-primary-700 transition-colors"
                                    >
                                        Edit
                                    </button>
                                </div>
                                @if(!$editingLLMSettings)
                                    <dl class="space-y-3">
                                        <div>
                                            <dt class="text-sm text-gray-600 dark:text-gray-400">Model</dt>
                                            <dd class="text-base font-medium text-gray-900 dark:text-white">{{ $llmData['model'] ?? 'Not set' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm text-gray-600 dark:text-gray-400">Temperature</dt>
                                            <dd class="text-base font-medium text-gray-900 dark:text-white">{{ $llmData['temperature'] ?? 'Default' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm text-gray-600 dark:text-gray-400">Max Tokens</dt>
                                            <dd class="text-base font-medium text-gray-900 dark:text-white">{{ $llmData['max_tokens'] ?? 'Default' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm text-gray-600 dark:text-gray-400">Top P</dt>
                                            <dd class="text-base font-medium text-gray-900 dark:text-white">{{ $llmData['top_p'] ?? '1.0' }}</dd>
                                        </div>
                                    </dl>
                                @else
                                    <form wire:submit.prevent="saveLLMSettings" class="space-y-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                                            <select 
                                                wire:model="llmSettings.model"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                            >
                                                <option value="gpt-4o">GPT-4o</option>
                                                <option value="gpt-4">GPT-4</option>
                                                <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                                <option value="claude-3-opus">Claude 3 Opus</option>
                                                <option value="claude-3-sonnet">Claude 3 Sonnet</option>
                                                <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Temperature</label>
                                            <input 
                                                type="number"
                                                step="0.1"
                                                min="0"
                                                max="2"
                                                wire:model="llmSettings.temperature"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                            >
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Max Tokens</label>
                                            <input 
                                                type="number"
                                                min="1"
                                                max="4000"
                                                wire:model="llmSettings.max_tokens"
                                                class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                            >
                                        </div>
                                        <div class="flex gap-2">
                                            <button 
                                                type="submit"
                                                class="px-3 py-1 text-sm bg-success-600 text-white rounded hover:bg-success-700"
                                            >
                                                Save
                                            </button>
                                            <button 
                                                type="button"
                                                wire:click="cancelEditingLLMSettings"
                                                class="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Phone Numbers</h3>
                                <ul class="space-y-2">
                                    @foreach($phoneNumbers as $phone)
                                        @if(($phone['agent_id'] ?? $phone['inbound_agent_id'] ?? null) === $selectedAgent['agent_id'])
                                            <li class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $phone['phone_number'] }}</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $phone['nickname'] ?? 'No nickname' }}</p>
                                                </div>
                                                @if($phone['inbound_webhook_url'] ?? null)
                                                    <span class="text-xs text-green-600 dark:text-green-400">Webhook ✓</span>
                                                @endif
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Prompt Editor Tab --}}
                    <div x-show="selectedTab === 'prompt'" x-transition>
                        @if(!$editingPrompt)
                            <div class="space-y-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Prompt</h3>
                                    <button 
                                        wire:click="startEditingPrompt"
                                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                                    >
                                        Edit Prompt
                                    </button>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 max-h-96 overflow-y-auto">
                                    <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono">{{ $llmData['general_prompt'] ?? 'No prompt configured' }}</pre>
                                </div>
                            </div>
                        @else
                            <div class="space-y-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit System Prompt</h3>
                                    <div class="flex gap-2">
                                        <button 
                                            wire:click="savePrompt"
                                            class="px-4 py-2 bg-success-600 text-white rounded-lg hover:bg-success-700 transition-colors"
                                        >
                                            Save Changes
                                        </button>
                                        <button 
                                            wire:click="cancelEditingPrompt"
                                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                <textarea 
                                    wire:model="newPrompt"
                                    class="w-full h-96 p-4 font-mono text-sm bg-gray-900 text-gray-100 rounded-lg border border-gray-700 focus:border-primary-500 focus:ring-2 focus:ring-primary-500"
                                    placeholder="Enter your prompt here..."
                                ></textarea>
                            </div>
                        @endif
                    </div>

                    {{-- Custom Functions Tab --}}
                    <div x-show="selectedTab === 'functions'" x-transition class="retell-functions-wrapper">
                        <style>
                            /* Force modern styles with high specificity */
                            .retell-functions-wrapper .function-card-modern {
                                background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%) !important;
                                backdrop-filter: blur(10px) !important;
                                -webkit-backdrop-filter: blur(10px) !important;
                                border: 1px solid rgba(99, 102, 241, 0.3) !important;
                                border-radius: 16px !important;
                                padding: 24px !important;
                                position: relative !important;
                                overflow: hidden !important;
                                transition: all 0.3s ease !important;
                                margin-bottom: 16px !important;
                                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                            }
                            
                            .dark .retell-functions-wrapper .function-card-modern {
                                background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
                                border: 1px solid rgba(99, 102, 241, 0.4) !important;
                            }
                            
                            .retell-functions-wrapper .function-card-modern:hover {
                                transform: translateY(-4px) !important;
                                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                                border-color: rgba(99, 102, 241, 0.5) !important;
                            }
                        </style>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Custom Functions</h3>
                                <div class="flex items-center gap-3">
                                    <button 
                                        wire:click="startAddingFunction"
                                        class="btn-gradient-primary"
                                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; color: white !important; padding: 12px 24px !important; border-radius: 12px !important; font-weight: 600 !important; transition: all 0.3s ease !important; display: inline-flex !important; align-items: center !important; border: none !important;"
                                    >
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Add Function
                                    </button>
                                    <button 
                                        @click="showJsonView = !showJsonView"
                                        class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                                    >
                                        <span x-text="showJsonView ? 'Show Visual' : 'Show JSON'"></span>
                                    </button>
                                    <button 
                                        @click="if(window.applyModernStyles) window.applyModernStyles()"
                                        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="Force apply modern styles"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            @if($llmData['general_tools'] ?? false)
                                <div x-show="!showJsonView" class="space-y-4">
                                    {{-- Function Editor Modal/Inline --}}
                                    @if($editingFunction || $addingNewFunction)
                                        <div class="rounded-xl p-6 border-2" style="background: rgba(255, 255, 255, 0.05) !important; backdrop-filter: blur(10px) !important; -webkit-backdrop-filter: blur(10px) !important; border: 2px solid rgba(99, 102, 241, 0.2) !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;">
                                            <form wire:submit.prevent="saveFunction" class="space-y-6">
                                                <div class="flex items-center justify-between mb-6">
                                                    <h4 class="text-xl font-semibold text-gray-900 dark:text-white">
                                                        {{ $addingNewFunction ? 'Create New Function' : 'Edit Function' }}
                                                    </h4>
                                                    <button 
                                                        type="button"
                                                        wire:click="cancelEditingFunction"
                                                        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                                    >
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                
                                                {{-- Function Templates --}}
                                                @if($addingNewFunction)
                                                    <div class="mb-6">
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Start from Template</label>
                                                        <div class="grid grid-cols-3 gap-3">
                                                            @foreach([['name' => 'Weather API', 'icon' => '🌤️'], ['name' => 'Database Query', 'icon' => '🗄️'], ['name' => 'Send Email', 'icon' => '📧']] as $idx => $template)
                                                                <div 
                                                                    wire:click="applyFunctionTemplate({{ $idx }})"
                                                                    class="glass-card p-4 text-center hover:border-primary-500/50 transition-all hover:scale-105 cursor-pointer"
                                                                >
                                                                    <div class="text-3xl mb-2">{{ $template['icon'] }}</div>
                                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $template['name'] }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                {{-- Basic Information --}}
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Function Name</label>
                                                        <input 
                                                            type="text"
                                                            wire:model="functionEditor.name"
                                                            class="input-modern"
                                                            placeholder="e.g., check_weather"
                                                            {{ !$addingNewFunction ? 'readonly' : '' }}
                                                        >
                                                    </div>
                                                    
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                                        <select 
                                                            wire:model="functionEditor.type"
                                                            class="input-modern"
                                                        >
                                                            <option value="custom">Custom API</option>
                                                            <option value="check_availability_cal">Cal.com - Check Availability</option>
                                                            <option value="book_appointment_cal">Cal.com - Book Appointment</option>
                                                            <option value="end_call">System - End Call</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                                    <textarea 
                                                        wire:model="functionEditor.description"
                                                        class="input-modern h-20"
                                                        placeholder="Describe what this function does..."
                                                    ></textarea>
                                                </div>
                                                
                                                {{-- Custom Function Settings --}}
                                                @if($functionEditor['type'] === 'custom')
                                                    <div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                                        <h5 class="font-medium text-gray-900 dark:text-white mb-3">API Configuration</h5>
                                                        
                                                        <div class="grid grid-cols-4 gap-4">
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Method</label>
                                                                <select 
                                                                    wire:model="functionEditor.method"
                                                                    class="input-modern text-sm"
                                                                >
                                                                    <option value="GET">GET</option>
                                                                    <option value="POST">POST</option>
                                                                    <option value="PUT">PUT</option>
                                                                    <option value="DELETE">DELETE</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="col-span-3">
                                                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">URL</label>
                                                                <input 
                                                                    type="url"
                                                                    wire:model="functionEditor.url"
                                                                    class="input-modern text-sm font-mono"
                                                                    placeholder="https://api.example.com/endpoint"
                                                                >
                                                            </div>
                                                        </div>
                                                        
                                                        {{-- Headers --}}
                                                        <div>
                                                            <div class="flex items-center justify-between mb-2">
                                                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Headers</label>
                                                                <button 
                                                                    type="button"
                                                                    wire:click="addFunctionHeader"
                                                                    class="text-xs text-primary-600 hover:text-primary-700"
                                                                >
                                                                    + Add Header
                                                                </button>
                                                            </div>
                                                            <div class="space-y-2">
                                                                @foreach($functionEditor['headers'] as $idx => $header)
                                                                    <div class="flex gap-2">
                                                                        <input 
                                                                            type="text"
                                                                            wire:model="functionEditor.headers.{{ $idx }}.key"
                                                                            class="input-modern text-sm flex-1"
                                                                            placeholder="Header Name"
                                                                        >
                                                                        <input 
                                                                            type="text"
                                                                            wire:model="functionEditor.headers.{{ $idx }}.value"
                                                                            class="input-modern text-sm flex-1"
                                                                            placeholder="Header Value"
                                                                        >
                                                                        <button 
                                                                            type="button"
                                                                            wire:click="removeFunctionHeader({{ $idx }})"
                                                                            class="px-2 py-1 text-red-600 hover:text-red-700"
                                                                        >
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                {{-- Parameters --}}
                                                <div>
                                                    <div class="flex items-center justify-between mb-3">
                                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Parameters</label>
                                                        <button 
                                                            type="button"
                                                            wire:click="addFunctionParameter"
                                                            class="px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-lg text-sm hover:bg-primary-200 dark:hover:bg-primary-900/50 transition-colors"
                                                        >
                                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                            </svg>
                                                            Add Parameter
                                                        </button>
                                                    </div>
                                                    
                                                    <div class="space-y-3">
                                                        @foreach($functionEditor['parameters'] as $idx => $param)
                                                            <div class="parameter-card group">
                                                                <div class="flex items-start justify-between mb-3">
                                                                    <div class="flex-1 grid grid-cols-2 gap-3">
                                                                        <input 
                                                                            type="text"
                                                                            wire:model="functionEditor.parameters.{{ $idx }}.name"
                                                                            class="input-modern text-sm"
                                                                            placeholder="Parameter name"
                                                                        >
                                                                        <select 
                                                                            wire:model="functionEditor.parameters.{{ $idx }}.type"
                                                                            class="input-modern text-sm"
                                                                        >
                                                                            <option value="string">String</option>
                                                                            <option value="number">Number</option>
                                                                            <option value="boolean">Boolean</option>
                                                                            <option value="array">Array</option>
                                                                            <option value="object">Object</option>
                                                                        </select>
                                                                    </div>
                                                                    <button 
                                                                        type="button"
                                                                        wire:click="removeFunctionParameter({{ $idx }})"
                                                                        class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity text-red-600 hover:text-red-700"
                                                                    >
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                                <input 
                                                                    type="text"
                                                                    wire:model="functionEditor.parameters.{{ $idx }}.description"
                                                                    class="input-modern text-sm mb-2"
                                                                    placeholder="Parameter description"
                                                                >
                                                                <div class="grid grid-cols-2 gap-3">
                                                                    <div class="flex items-center">
                                                                        <input 
                                                                            type="checkbox"
                                                                            wire:model="functionEditor.parameters.{{ $idx }}.required"
                                                                            class="mr-2 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                                        >
                                                                        <label class="text-sm text-gray-700 dark:text-gray-300">Required</label>
                                                                    </div>
                                                                    <input 
                                                                        type="text"
                                                                        wire:model="functionEditor.parameters.{{ $idx }}.example"
                                                                        class="input-modern text-sm"
                                                                        placeholder="Example value"
                                                                    >
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                
                                                {{-- Voice Settings --}}
                                                <div class="grid grid-cols-2 gap-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                                                    <label class="flex items-center">
                                                        <input 
                                                            type="checkbox"
                                                            wire:model="functionEditor.speak_during_execution"
                                                            class="mr-2 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                        >
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">Speak during execution</span>
                                                    </label>
                                                    <label class="flex items-center">
                                                        <input 
                                                            type="checkbox"
                                                            wire:model="functionEditor.speak_after_execution"
                                                            class="mr-2 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                        >
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">Speak after execution</span>
                                                    </label>
                                                </div>
                                                
                                                @if($functionEditor['speak_during_execution'])
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Execution Message</label>
                                                        <input 
                                                            type="text"
                                                            wire:model="functionEditor.execution_message"
                                                            class="input-modern"
                                                            placeholder="Message to speak while function executes..."
                                                        >
                                                    </div>
                                                @endif
                                                
                                                {{-- Action Buttons --}}
                                                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                                    <button 
                                                        type="button"
                                                        wire:click="cancelEditingFunction"
                                                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                                                    >
                                                        Cancel
                                                    </button>
                                                    <button 
                                                        type="submit"
                                                        class="px-4 py-2 bg-gradient-to-r from-success-600 to-success-700 text-white rounded-lg hover:from-success-700 hover:to-success-800 transition-all shadow-lg hover:shadow-xl"
                                                    >
                                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        {{ $addingNewFunction ? 'Create Function' : 'Save Changes' }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                    
                                    {{-- Functions List --}}
                                    @foreach($llmData['general_tools'] as $idx => $function)
                                        @php
                                            $details = $functionDetails[$function['name']] ?? null;
                                            $isBookingFunction = in_array($function['type'] ?? '', ['check_availability_cal', 'book_appointment_cal']);
                                            $functionType = $function['type'] ?? 'custom';
                                        @endphp
                                        <div class="function-card-modern group {{ $isBookingFunction ? 'border-primary-500/30' : '' }}" 
                                             style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important; 
                                                    backdrop-filter: blur(20px) !important; 
                                                    -webkit-backdrop-filter: blur(20px) !important; 
                                                    border: 1px solid rgba(99, 102, 241, 0.4) !important; 
                                                    border-radius: 24px !important; 
                                                    padding: 32px !important; 
                                                    position: relative !important; 
                                                    overflow: hidden !important; 
                                                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important; 
                                                    margin-bottom: 24px !important; 
                                                    box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
                                                    transform: translateZ(0) !important;
                                                    will-change: transform, box-shadow !important;"
                                             onmouseover="this.style.cssText += 'transform: translateY(-8px) scale(1.02) !important; box-shadow: 0 20px 60px -15px rgba(99, 102, 241, 0.4), 0 0 0 1px rgba(99, 102, 241, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 0 120px -20px rgba(99, 102, 241, 0.5) !important; border-color: rgba(99, 102, 241, 0.6) !important;'"
                                             onmouseout="this.style.cssText += 'transform: translateZ(0) !important; box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important; border-color: rgba(99, 102, 241, 0.4) !important;'">
                                            <div 
                                                @click="expandedFunctions.includes({{ $idx }}) ? expandedFunctions = expandedFunctions.filter(i => i !== {{ $idx }}) : expandedFunctions.push({{ $idx }})"
                                                class="cursor-pointer"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-4">
                                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white" style="background: {{ $isBookingFunction ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : ($functionType === 'end_call' ? 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' : 'linear-gradient(135deg, #10b981 0%, #059669 100%)') }} !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;">
                                                            @if($isBookingFunction)
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                </svg>
                                                            @elseif($functionType === 'end_call')
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"></path>
                                                                </svg>
                                                            @else
                                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                                </svg>
                                                            @endif
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h4 class="font-semibold text-gray-900 dark:text-white text-lg">
                                                                    {{ $function['name'] }}
                                                                </h4>
                                                                <span class="function-badge {{ $isBookingFunction ? 'cal' : ($functionType === 'end_call' ? 'system' : 'custom') }}">
                                                                    @if($isBookingFunction)
                                                                        Cal.com
                                                                    @elseif($functionType === 'end_call')
                                                                        System
                                                                    @else
                                                                        Custom
                                                                    @endif
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($function['description'] ?? 'No description', 100) }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        @if($function['speak_during_execution'] ?? false)
                                                            <div class="flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400">
                                                                <svg class="w-4 h-4 icon-pulse" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"></path>
                                                                </svg>
                                                                <span>Live</span>
                                                            </div>
                                                        @endif
                                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                                                            <button 
                                                                wire:click.stop="startEditingFunction('{{ $function['name'] }}')"
                                                                class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                                title="Edit Function"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </button>
                                                            <button 
                                                                wire:click.stop="deleteFunction('{{ $function['name'] }}')"
                                                                wire:confirm="Are you sure you want to delete this function?"
                                                                class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                                                title="Delete Function"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <button 
                                                            wire:click.stop="startTestingFunction('{{ $function['name'] }}')"
                                                            class="inline-flex items-center px-4 py-2 text-white rounded-lg transition-all"
                                                            style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important; font-weight: 600 !important; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;"
                                                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 20px -5px rgba(59, 130, 246, 0.5)'"
                                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1)'"
                                                        >
                                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            Test
                                                        </button>
                                                        <div class="cursor-pointer">
                                                            <svg x-show="!expandedFunctions.includes({{ $idx }})" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                            </svg>
                                                            <svg x-show="expandedFunctions.includes({{ $idx }})" class="w-5 h-5 text-gray-400 transition-transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div 
                                                x-show="expandedFunctions.includes({{ $idx }})"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 transform translate-y-0"
                                                x-transition:leave-end="opacity-0 transform -translate-y-2"
                                                class="mt-4 pt-4 border-t border-gray-200/50 dark:border-gray-700/50"
                                            >
                                                <div class="space-y-4">
                                                    {{-- Function Details in Modern Cards --}}
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        @if($functionType === 'custom')
                                                            <div class="glass-card p-4">
                                                                <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Endpoint</h5>
                                                                <div class="code-editor p-3 text-xs">
                                                                    <span class="text-purple-400">{{ strtoupper($function['method'] ?? 'GET') }}</span>
                                                                    <span class="text-gray-300 ml-2">{{ $function['url'] ?? 'Not configured' }}</span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        
                                                        <div class="glass-card p-4">
                                                            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Configuration</h5>
                                                            <div class="space-y-2">
                                                                <div class="flex items-center justify-between">
                                                                    <span class="text-sm text-gray-600 dark:text-gray-400">Type</span>
                                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($functionType) }}</span>
                                                                </div>
                                                                @if($function['speak_during_execution'] ?? false)
                                                                    <div class="flex items-center justify-between">
                                                                        <span class="text-sm text-gray-600 dark:text-gray-400">Voice During</span>
                                                                        <span class="flex items-center text-sm text-green-600 dark:text-green-400">
                                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                                            </svg>
                                                                            Active
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Parameters Section with Modern Design --}}
                                                    @if($details && isset($details['parameters']) && count($details['parameters']) > 0)
                                                        <div class="mt-4">
                                                            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Parameters</h5>
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                                @foreach($details['parameters'] as $param)
                                                                    <div class="parameter-card group hover:shadow-lg">
                                                                        <div class="flex items-start justify-between mb-2">
                                                                            <div class="flex items-center gap-2">
                                                                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br {{ $param['type'] === 'string' ? 'from-green-400 to-green-500' : 'from-blue-400 to-blue-500' }} flex items-center justify-center text-white text-xs font-bold shadow">
                                                                                    {{ strtoupper(substr($param['type'], 0, 1)) }}
                                                                                </div>
                                                                                <div>
                                                                                    <div class="font-semibold text-gray-900 dark:text-white">
                                                                                        {{ $param['name'] }}
                                                                                    </div>
                                                                                    <div class="flex items-center gap-2 mt-0.5">
                                                                                        <span class="text-xs text-gray-500">{{ $param['type'] }}</span>
                                                                                        @if($param['required'])
                                                                                            <span class="text-xs text-red-600 dark:text-red-400 font-medium">• Required</span>
                                                                                        @else
                                                                                            <span class="text-xs text-gray-400">• Optional</span>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                                                            {{ $param['description'] }}
                                                                        </p>
                                                                        @if(isset($param['example']))
                                                                            <div class="mt-3 p-2 bg-gray-900/5 dark:bg-gray-900/50 rounded-lg">
                                                                                <code class="text-xs text-primary-600 dark:text-primary-400 font-mono">
                                                                                    {{ $param['example'] }}
                                                                                </code>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- Quick Actions --}}
                                                    <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-200/50 dark:border-gray-700/50">
                                                        <div class="flex items-center gap-2">
                                                            <button 
                                                                wire:click="duplicateFunction('{{ $function['name'] }}')"
                                                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 flex items-center gap-1"
                                                                title="Duplicate Function"
                                                            >
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                                </svg>
                                                                Duplicate
                                                            </button>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            @php
                                                                $updatedAt = $function['updated_at'] ?? null;
                                                            @endphp
                                                            @if($updatedAt)
                                                                <span class="text-xs text-gray-500">Last updated: {{ \Carbon\Carbon::parse($updatedAt)->diffForHumans() }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- JSON View --}}
                                <div x-show="showJsonView" class="code-editor rounded-xl p-6 overflow-x-auto">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                        </div>
                                        <button 
                                            @click="navigator.clipboard.writeText(JSON.stringify({{ json_encode($llmData['general_tools']) }}, null, 2))"
                                            class="text-xs text-gray-400 hover:text-gray-200 flex items-center gap-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            Copy
                                        </button>
                                    </div>
                                    <pre class="text-sm text-gray-100 font-mono">{{ json_encode($llmData['general_tools'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">No custom functions configured for this agent.</p>
                                    <button 
                                        wire:click="startAddingFunction"
                                        class="btn-modern bg-gradient-to-r from-primary-600 to-primary-700 text-white"
                                    >
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Create Your First Function
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Test Console Tab --}}
                    <div x-show="selectedTab === 'testing'" x-transition>
                        @if($testingFunction)
                            <div class="space-y-6">
                                <div class="glass-card rounded-xl p-6">
                                    <div class="flex items-center justify-between mb-6">
                                        <div>
                                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                                Testing: {{ $testingFunction }}
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                @if(isset($functionDetails[$testingFunction]))
                                                    {{ $functionDetails[$testingFunction]['description'] }}
                                                @endif
                                            </p>
                                        </div>
                                        <button 
                                            wire:click="$set('testingFunction', null)"
                                            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                                        >
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Test Inputs --}}
                                    <div class="space-y-4">
                                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Input Parameters</h4>
                                        @if(isset($functionDetails[$testingFunction]['parameters']))
                                            <div class="grid grid-cols-1 gap-4">
                                                @foreach($functionDetails[$testingFunction]['parameters'] as $param)
                                                    <div class="parameter-card hover:shadow-md">
                                                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                                            {{ $param['name'] }}
                                                            @if($param['required'])
                                                                <span class="text-red-500 text-xs font-normal ml-1">(required)</span>
                                                            @else
                                                                <span class="text-gray-400 text-xs font-normal ml-1">(optional)</span>
                                                            @endif
                                                        </label>
                                                        <input 
                                                            type="text"
                                                            wire:model="testInputs.{{ $param['name'] }}"
                                                            placeholder="{{ $param['example'] ?? 'Enter ' . $param['name'] }}"
                                                            class="input-modern {{ $param['required'] && !$testInputs[$param['name']] ? 'border-red-300' : '' }}"
                                                        >
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 leading-relaxed">{{ $param['description'] }}</p>
                                                        @if(isset($param['type']))
                                                            <div class="mt-2 flex items-center gap-2">
                                                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                                                    Type: {{ $param['type'] }}
                                                                </span>
                                                                @if(isset($param['example']))
                                                                    <span class="text-xs text-gray-500 dark:text-gray-500">
                                                                        Example: <code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ $param['example'] }}</code>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400">This function has no input parameters.</p>
                                        @endif
                                    </div>

                                    {{-- Execute Test --}}
                                    <div class="flex justify-center pt-4">
                                        <button 
                                            wire:click="executeTest"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            class="btn-modern bg-gradient-to-r from-primary-600 to-primary-700 text-white px-8 py-3 text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5"
                                        >
                                            <div wire:loading.remove wire:target="executeTest" class="flex items-center">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Execute Test
                                            </div>
                                            <div wire:loading wire:target="executeTest" class="flex items-center">
                                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Testing...
                                            </div>
                                        </button>
                                    </div>
                                </div>

                            {{-- Test Results --}}
                            @if($testResult)
                                <div class="mt-6">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Test Result</h4>
                                    <div class="code-editor rounded-xl overflow-hidden">
                                        <div class="flex items-center justify-between p-3 bg-gray-800 border-b border-gray-700">
                                            <div class="flex items-center gap-4">
                                                @if($testResult['success'])
                                                    <div class="flex items-center gap-2 text-green-400">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        <span class="font-medium">Success</span>
                                                    </div>
                                                    <span class="text-gray-400 text-sm">Status: {{ $testResult['status'] }}</span>
                                                @else
                                                    <div class="flex items-center gap-2 text-red-400">
                                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        <span class="font-medium">Failed</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <button 
                                                @click="navigator.clipboard.writeText(JSON.stringify({{ json_encode($testResult['success'] ? $testResult['body'] : ['error' => $testResult['error']]) }}, null, 2))"
                                                class="text-xs text-gray-400 hover:text-gray-200 flex items-center gap-1"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                Copy
                                            </button>
                                        </div>
                                        <div class="p-4 overflow-x-auto max-h-96">
                                            @if($testResult['success'])
                                                <pre class="text-sm text-gray-100 font-mono">{{ json_encode($testResult['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                <div class="text-red-400 font-mono">
                                                    {{ $testResult['error'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-16">
                            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-2 text-lg">No function selected for testing</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm">Select a function from the Functions tab to begin testing</p>
                        </div>
                    @endif
                </div>
                    
                    {{-- Agent Settings Tab --}}
                    <div x-show="selectedTab === 'settings'" x-transition>
                        @if(!$editingAgentSettings)
                            <div class="space-y-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Agent Settings</h3>
                                    <button 
                                        wire:click="startEditingAgentSettings"
                                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                                    >
                                        Edit Settings
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Voice Settings</h4>
                                        <dl class="space-y-3">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Voice ID</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedAgent['voice_id'] ?? 'Default' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Language</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedAgent['language'] ?? 'en-US' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Voice Speed</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedAgent['voice_speed'] ?? '1.0' }}x</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Voice Temperature</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedAgent['voice_temperature'] ?? '1.0' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Interaction Settings</h4>
                                        <dl class="space-y-3">
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Interruption Sensitivity</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedAgent['interruption_sensitivity'] ?? '1' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Responsiveness</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedAgent['responsiveness'] ?? '1' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Backchannel</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ ($selectedAgent['enable_backchannel'] ?? true) ? 'Enabled' : 'Disabled' }}</dd>
                                            </div>
                                            <div class="flex justify-between">
                                                <dt class="text-sm text-gray-600 dark:text-gray-400">Ambient Sound</dt>
                                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($selectedAgent['ambient_sound'] ?? 'off') }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        @else
                            <form wire:submit.prevent="saveAgentSettings" class="space-y-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Agent Settings</h3>
                                    <div class="flex gap-2">
                                        <button 
                                            type="submit"
                                            class="px-4 py-2 bg-success-600 text-white rounded-lg hover:bg-success-700 transition-colors"
                                        >
                                            Save Changes
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="cancelEditingAgentSettings"
                                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Agent Name</label>
                                        <input 
                                            type="text"
                                            wire:model="agentSettings.agent_name"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Voice ID</label>
                                        <select 
                                            wire:model="agentSettings.voice_id"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                        >
                                            <option value="">Default Voice</option>
                                            <option value="11labs-Adrian">Adrian (11Labs)</option>
                                            <option value="11labs-Bella">Bella (11Labs)</option>
                                            <option value="openai-alloy">Alloy (OpenAI)</option>
                                            <option value="openai-nova">Nova (OpenAI)</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Voice Speed</label>
                                        <input 
                                            type="number"
                                            step="0.1"
                                            min="0.5"
                                            max="2"
                                            wire:model="agentSettings.voice_speed"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Interruption Sensitivity</label>
                                        <input 
                                            type="number"
                                            step="0.1"
                                            min="0"
                                            max="2"
                                            wire:model="agentSettings.interruption_sensitivity"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                        >
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enable Backchannel</label>
                                        <select 
                                            wire:model="agentSettings.enable_backchannel"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                        >
                                            <option value="1">Enabled</option>
                                            <option value="0">Disabled</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ambient Sound</label>
                                        <select 
                                            wire:model="agentSettings.ambient_sound"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                                        >
                                            <option value="off">Off</option>
                                            <option value="office">Office</option>
                                            <option value="cafe">Cafe</option>
                                            <option value="restaurant">Restaurant</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                    
                    {{-- Phone Numbers Tab --}}
                    <div x-show="selectedTab === 'phone'" x-transition>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Phone Number Management</h3>
                                <button 
                                    wire:click="loadPhoneNumbers"
                                    class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                @foreach($phoneNumbers as $phone)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                        @if($editingPhoneNumber === ($phone['phone_number_id'] ?? $phone['phone_number'] ?? ''))
                                            <form wire:submit.prevent="savePhoneNumber" class="space-y-4">
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nickname</label>
                                                        <input 
                                                            type="text"
                                                            wire:model="phoneNumberConfig.nickname"
                                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                            placeholder="Main Office"
                                                        >
                                                    </div>
                                                    
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inbound Agent</label>
                                                        <select 
                                                            wire:model="phoneNumberConfig.inbound_agent_id"
                                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                        >
                                                            <option value="">No Agent</option>
                                                            @foreach($agents as $agent)
                                                                <option value="{{ $agent['agent_id'] }}">{{ $agent['agent_name'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="col-span-2">
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inbound Webhook URL</label>
                                                        <input 
                                                            type="url"
                                                            wire:model="phoneNumberConfig.inbound_webhook_url"
                                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                            placeholder="https://api.askproai.de/webhook/phone-routing"
                                                        >
                                                    </div>
                                                </div>
                                                
                                                <div class="flex justify-end gap-2">
                                                    <button 
                                                        type="submit"
                                                        class="px-3 py-1.5 bg-success-600 text-white rounded-lg hover:bg-success-700 transition-colors"
                                                    >
                                                        Save
                                                    </button>
                                                    <button 
                                                        type="button"
                                                        wire:click="cancelEditingPhoneNumber"
                                                        class="px-3 py-1.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        @else
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $phone['phone_number'] }}</h4>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $phone['nickname'] ?? 'No nickname' }}</p>
                                                    @if($phone['inbound_agent_id'])
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                            Agent: {{ collect($agents)->firstWhere('agent_id', $phone['inbound_agent_id'])['agent_name'] ?? 'Unknown' }}
                                                        </p>
                                                    @endif
                                                    @if($phone['inbound_webhook_url'])
                                                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">✓ Webhook configured</p>
                                                    @endif
                                                </div>
                                                <button 
                                                    wire:click="startEditingPhoneNumber('{{ $phone['phone_number_id'] ?? $phone['phone_number'] ?? '' }}')"
                                                    class="px-3 py-1.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                                                >
                                                    Configure
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    {{-- Webhook Configuration Tab --}}
                    <div x-show="selectedTab === 'webhook'" x-transition>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Webhook Configuration</h3>
                                <button 
                                    wire:click="startEditingWebhook"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                                >
                                    Configure Webhooks
                                </button>
                            </div>
                            
                            @if($webhookConfig)
                                @if(!$editingWebhook)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                                        <dl class="space-y-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL</dt>
                                                <dd class="text-sm font-mono bg-gray-100 dark:bg-gray-900 p-3 rounded">
                                                    {{ $webhookConfig['webhook_url'] ?: 'Not configured' }}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook Secret</dt>
                                                <dd class="text-sm font-mono bg-gray-100 dark:bg-gray-900 p-3 rounded">
                                                    {{ $webhookConfig['webhook_secret'] ? '••••••••••••••••' : 'Not configured' }}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Events</dt>
                                                <dd class="flex gap-2 mt-2">
                                                    @foreach($webhookConfig['events'] as $event => $enabled)
                                                        <span class="px-3 py-1 text-xs rounded-full {{ $enabled ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                                            {{ str_replace('_', ' ', $event) }}
                                                        </span>
                                                    @endforeach
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                @else
                                    <form wire:submit.prevent="saveWebhookConfig" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL</label>
                                            <input 
                                                type="url"
                                                wire:model="webhookConfig.webhook_url"
                                                class="w-full px-3 py-2 font-mono text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                placeholder="https://api.askproai.de/api/retell/webhook"
                                            >
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">This URL will receive all call events from Retell</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook Secret</label>
                                            <input 
                                                type="text"
                                                wire:model="webhookConfig.webhook_secret"
                                                class="w-full px-3 py-2 font-mono text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                placeholder="key_••••••••••••••••"
                                            >
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used to verify webhook signatures (different from API key)</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Events to Receive</label>
                                            <div class="space-y-2">
                                                <label class="flex items-center">
                                                    <input 
                                                        type="checkbox"
                                                        wire:model="webhookConfig.events.call_started"
                                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                    >
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Call Started</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input 
                                                        type="checkbox"
                                                        wire:model="webhookConfig.events.call_ended"
                                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                    >
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Call Ended</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input 
                                                        type="checkbox"
                                                        wire:model="webhookConfig.events.call_analyzed"
                                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                    >
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Call Analyzed</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <button 
                                                type="submit"
                                                class="px-4 py-2 bg-success-600 text-white rounded-lg hover:bg-success-700 transition-colors"
                                            >
                                                Save Configuration
                                            </button>
                                            <button 
                                                type="button"
                                                wire:click="cancelEditingWebhook"
                                                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            @else
                                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-6 text-center">
                                    <p class="text-gray-500 dark:text-gray-400">Click "Configure Webhooks" to set up webhook configuration.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif($selectedAgent && !$llmData)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6 text-center">
                <p class="text-yellow-800 dark:text-yellow-200">This agent does not use a Retell LLM configuration.</p>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-12 text-center">
                <p class="text-gray-500 dark:text-gray-400">Select an agent above to view and edit its configuration.</p>
            </div>
        @endif
    </div>
    
    {{-- Removed performance-killing scripts - Issue #452
    <link rel="stylesheet" href="/css/force-modern-styles.css?v={{ time() }}" />
    <script src="/js/force-modern-styles.js?v={{ time() }}"></script>
    --}}
    
    {{-- Inline critical styles immediately --}}
    <style id="critical-modern-styles">
        /* Immediate critical styles */
        .function-card-modern {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(99, 102, 241, 0.4) !important;
            border-radius: 24px !important;
            padding: 32px !important;
            box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
        }
    </style>
    
    @push('scripts')
    <script>
        // Additional inline script for immediate application
        (function() {
            // Apply styles immediately on page load
            function applyUltraModernStyles() {
                const styles = `
                    <style id="ultra-modern-styles-${Date.now()}">
                        /* Ultra aggressive styles with multiple selectors */
                        .function-card-modern,
                        div.function-card-modern,
                        .retell-functions-wrapper .function-card-modern,
                        body .function-card-modern,
                        html body .function-card-modern,
                        .fi-page .function-card-modern,
                        .fi-page-content .function-card-modern,
                        div[class*="function-card-modern"],
                        div[class~="function-card-modern"],
                        *:has(> .function-card-modern) .function-card-modern {
                            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
                            backdrop-filter: blur(20px) !important;
                            -webkit-backdrop-filter: blur(20px) !important;
                            border: 1px solid rgba(99, 102, 241, 0.4) !important;
                            border-radius: 24px !important;
                            padding: 32px !important;
                            position: relative !important;
                            overflow: hidden !important;
                            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                            margin-bottom: 24px !important;
                            box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
                            transform: translateZ(0) !important;
                        }
                        
                        /* Hover state with maximum specificity */
                        html body .fi-page .function-card-modern:hover {
                            transform: translateY(-8px) scale(1.02) !important;
                            box-shadow: 0 20px 60px -15px rgba(99, 102, 241, 0.4), 0 0 0 1px rgba(99, 102, 241, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 0 120px -20px rgba(99, 102, 241, 0.5) !important;
                            border-color: rgba(99, 102, 241, 0.6) !important;
                        }
                    </style>
                `;
                
                // Insert at multiple locations
                document.head.insertAdjacentHTML('beforeend', styles);
                document.body.insertAdjacentHTML('beforeend', styles);
                
                // Also apply inline styles to each card
                document.querySelectorAll('.function-card-modern').forEach(card => {
                    card.setAttribute('style', `
                        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
                        backdrop-filter: blur(20px) !important;
                        -webkit-backdrop-filter: blur(20px) !important;
                        border: 1px solid rgba(99, 102, 241, 0.4) !important;
                        border-radius: 24px !important;
                        padding: 32px !important;
                        position: relative !important;
                        overflow: hidden !important;
                        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                        margin-bottom: 24px !important;
                        box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
                        transform: translateZ(0) !important;
                    `);
                });
            }
            
            // Apply immediately
            applyUltraModernStyles();
            
            // Apply on various events
            document.addEventListener('DOMContentLoaded', applyUltraModernStyles);
            window.addEventListener('load', applyUltraModernStyles);
            
            // Apply after Livewire updates
            if (window.Livewire) {
                Livewire.hook('component.initialized', applyUltraModernStyles);
                Livewire.hook('element.updated', applyUltraModernStyles);
                Livewire.hook('message.processed', applyUltraModernStyles);
            }
            
            // Apply periodically
            setInterval(applyUltraModernStyles, 30000);
            
            // Override any style changes
            const styleObserver = new MutationObserver(() => {
                applyUltraModernStyles();
            });
            
            styleObserver.observe(document.head, {
                childList: true,
                subtree: true
            });
            
            styleObserver.observe(document.body, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: true,
                subtree: true
            });
        })();
    </script>
    @endpush
    
    @push('styles')
    <style>
        /* Additional forced styles with maximum specificity */
        @layer force-modern {
            .function-card-modern {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
                backdrop-filter: blur(20px) !important;
                -webkit-backdrop-filter: blur(20px) !important;
                border: 1px solid rgba(99, 102, 241, 0.4) !important;
                border-radius: 24px !important;
                padding: 32px !important;
            }
        }
    </style>
    @endpush
</x-filament-panels::page>