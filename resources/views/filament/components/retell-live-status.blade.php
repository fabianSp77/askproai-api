<div class="retell-live-status" x-data="retellLiveStatus()" x-init="init()">
    <div class="space-y-4">
        <!-- Gesamt-Status -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="relative">
                        <div class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div x-show="isAllActive" class="absolute -bottom-1 -right-1 h-3 w-3 bg-green-400 rounded-full animate-pulse"></div>
                        <div x-show="!isAllActive" class="absolute -bottom-1 -right-1 h-3 w-3 bg-red-400 rounded-full"></div>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">KI-Telefonie Status</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span x-text="activeAgentsCount"></span> von <span x-text="totalAgentsCount"></span> Agenten aktiv
                        </p>
                    </div>
                </div>
                
                <div class="flex space-x-2">
                    <button @click="refreshStatus()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" :class="{'animate-spin': isRefreshing}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Agent Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="agent in agents" :key="agent.id">
                <div class="bg-white dark:bg-gray-800 rounded-lg border" 
                     :class="{
                         'border-green-200 dark:border-green-700': agent.status === 'active',
                         'border-yellow-200 dark:border-yellow-700': agent.status === 'busy',
                         'border-red-200 dark:border-red-700': agent.status === 'offline'
                     }">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="font-medium text-gray-900 dark:text-white flex items-center">
                                <div class="h-2 w-2 rounded-full mr-2"
                                     :class="{
                                         'bg-green-400': agent.status === 'active',
                                         'bg-yellow-400': agent.status === 'busy',
                                         'bg-red-400': agent.status === 'offline'
                                     }"></div>
                                <span x-text="agent.name"></span>
                            </h5>
                            <span class="text-xs px-2 py-1 rounded-full"
                                  :class="{
                                      'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': agent.status === 'active',
                                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': agent.status === 'busy',
                                      'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': agent.status === 'offline'
                                  }"
                                  x-text="agent.status_label"></span>
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Anrufe heute:</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="agent.calls_today"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Ø Gesprächszeit:</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="agent.avg_call_duration"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Erfolgsrate:</span>
                                <span class="font-medium" 
                                      :class="{
                                          'text-green-600 dark:text-green-400': agent.success_rate >= 80,
                                          'text-yellow-600 dark:text-yellow-400': agent.success_rate >= 60 && agent.success_rate < 80,
                                          'text-red-600 dark:text-red-400': agent.success_rate < 60
                                      }">
                                    <span x-text="agent.success_rate"></span>%
                                </span>
                            </div>
                        </div>
                        
                        <!-- Aktuelle Aktivität -->
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <template x-if="agent.current_call">
                                <div class="flex items-center text-sm text-yellow-600 dark:text-yellow-400">
                                    <svg class="w-4 h-4 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                    </svg>
                                    Im Gespräch seit <span x-text="agent.current_call_duration"></span>
                                </div>
                            </template>
                            <template x-if="!agent.current_call && agent.status === 'active'">
                                <div class="flex items-center text-sm text-green-600 dark:text-green-400">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Bereit für Anrufe
                                </div>
                            </template>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="mt-3 flex space-x-2">
                            <button @click="testAgent(agent.id)" 
                                    class="flex-1 px-3 py-1 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 text-sm rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/50">
                                Test-Anruf
                            </button>
                            <button @click="viewAgentDetails(agent.id)" 
                                    class="flex-1 px-3 py-1 bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                Details
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Keine Agenten -->
        <template x-if="agents.length === 0">
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Keine KI-Agenten konfiguriert</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Konfigurieren Sie KI-Agenten für diese Filiale.</p>
                <div class="mt-3">
                    <button @click="$wire.openAgentConfiguration()" 
                            class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                        Agent hinzufügen
                    </button>
                </div>
            </div>
        </template>
    </div>
    
    <script>
        function retellLiveStatus() {
            return {
                agents: [],
                isRefreshing: false,
                totalAgentsCount: 0,
                activeAgentsCount: 0,
                isAllActive: false,
                
                init() {
                    this.loadAgentStatus();
                    // Auto-refresh alle 30 Sekunden
                    setInterval(() => this.loadAgentStatus(), 30000);
                },
                
                async loadAgentStatus() {
                    try {
                        const response = await fetch('/api/branch/' + @json($getRecord()?->id) + '/retell-agents-status');
                        const data = await response.json();
                        
                        this.agents = data.agents.map(agent => {
                            // Simuliere Live-Daten für Demo
                            const statuses = ['active', 'busy', 'offline'];
                            const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
                            
                            return {
                                ...agent,
                                status: randomStatus,
                                status_label: this.getStatusLabel(randomStatus),
                                calls_today: Math.floor(Math.random() * 50),
                                avg_call_duration: Math.floor(Math.random() * 300) + 's',
                                success_rate: Math.floor(Math.random() * 40) + 60,
                                current_call: randomStatus === 'busy' ? {
                                    duration: Math.floor(Math.random() * 600)
                                } : null,
                                current_call_duration: randomStatus === 'busy' ? 
                                    this.formatDuration(Math.floor(Math.random() * 600)) : null
                            };
                        });
                        
                        this.updateCounts();
                    } catch (error) {
                        console.error('Fehler beim Laden der Agent-Status:', error);
                    }
                },
                
                updateCounts() {
                    this.totalAgentsCount = this.agents.length;
                    this.activeAgentsCount = this.agents.filter(a => a.status === 'active').length;
                    this.isAllActive = this.totalAgentsCount > 0 && this.activeAgentsCount === this.totalAgentsCount;
                },
                
                getStatusLabel(status) {
                    const labels = {
                        'active': 'Bereit',
                        'busy': 'Im Gespräch',
                        'offline': 'Offline'
                    };
                    return labels[status] || status;
                },
                
                formatDuration(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${minutes}:${secs.toString().padStart(2, '0')}`;
                },
                
                async refreshStatus() {
                    this.isRefreshing = true;
                    await this.loadAgentStatus();
                    setTimeout(() => {
                        this.isRefreshing = false;
                    }, 500);
                },
                
                testAgent(agentId) {
                    this.$wire.testRetellAgent(agentId);
                },
                
                viewAgentDetails(agentId) {
                    this.$wire.openAgentDetails(agentId);
                }
            }
        }
    </script>
</div>
