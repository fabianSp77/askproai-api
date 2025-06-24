<x-filament-panels::page>
    <div class="space-y-6" 
         x-data="{ 
             activeTab: 'dashboard',
             showAgentDetails: null,
             showWebhookDetails: false,
             testResults: null
         }"
         wire:poll.30s="refreshMetrics">
        
        {{-- Navigation Tabs --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'dashboard'"
                        :class="activeTab === 'dashboard' 
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-squares-2x2 class="w-5 h-5" />
                        Dashboard
                    </div>
                </button>
                
                <button @click="activeTab = 'agents'"
                        :class="activeTab === 'agents' 
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-user-group class="w-5 h-5" />
                        Agents
                    </div>
                </button>
                
                <button @click="activeTab = 'phone-numbers'"
                        :class="activeTab === 'phone-numbers' 
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-phone class="w-5 h-5" />
                        Phone Numbers
                    </div>
                </button>
                
                <button @click="activeTab = 'webhooks'"
                        :class="activeTab === 'webhooks' 
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-link class="w-5 h-5" />
                        Webhooks
                    </div>
                </button>
                
                <button @click="activeTab = 'testing'"
                        :class="activeTab === 'testing' 
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-beaker class="w-5 h-5" />
                        Testing
                    </div>
                </button>
                
                <button @click="activeTab = 'debugging'"
                        :class="activeTab === 'debugging' 
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-bug-ant class="w-5 h-5" />
                        Debugging
                    </div>
                </button>
            </nav>
        </div>
        
        {{-- Dashboard Tab --}}
        <div x-show="activeTab === 'dashboard'" x-transition>
            {{-- Status Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {{-- Agents Status --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Agents</p>
                                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                                    {{ $metrics['active_agents'] }}
                                    <span class="text-lg text-gray-500 dark:text-gray-400">/ {{ $metrics['total_agents'] }}</span>
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-full">
                                <x-heroicon-m-user-group class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" @click.prevent="activeTab = 'agents'" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                Manage agents →
                            </a>
                        </div>
                    </div>
                </div>
                
                {{-- Phone Numbers Status --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Connected Numbers</p>
                                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                                    {{ $metrics['connected_phone_numbers'] }}
                                    <span class="text-lg text-gray-500 dark:text-gray-400">/ {{ $metrics['total_phone_numbers'] }}</span>
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-full">
                                <x-heroicon-m-phone class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" @click.prevent="activeTab = 'phone-numbers'" class="text-sm text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300">
                                Configure numbers →
                            </a>
                        </div>
                    </div>
                </div>
                
                {{-- Webhook Health --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Webhook Health</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <div @class([
                                        'w-3 h-3 rounded-full animate-pulse',
                                        'bg-green-500' => $this->webhookHealthColor === 'success',
                                        'bg-yellow-500' => $this->webhookHealthColor === 'warning',
                                        'bg-red-500' => $this->webhookHealthColor === 'danger',
                                        'bg-gray-500' => $this->webhookHealthColor === 'gray',
                                    ])></div>
                                    <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $this->webhookHealthText }}
                                    </span>
                                </div>
                                @if($metrics['last_webhook_time'])
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Last activity: {{ Carbon::parse($metrics['last_webhook_time'])->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                            <div @class([
                                'p-3 rounded-full',
                                'bg-green-100 dark:bg-green-900/20' => $this->webhookHealthColor === 'success',
                                'bg-yellow-100 dark:bg-yellow-900/20' => $this->webhookHealthColor === 'warning',
                                'bg-red-100 dark:bg-red-900/20' => $this->webhookHealthColor === 'danger',
                                'bg-gray-100 dark:bg-gray-900/20' => $this->webhookHealthColor === 'gray',
                            ])>
                                <x-heroicon-m-link @class([
                                    'w-6 h-6',
                                    'text-green-600 dark:text-green-400' => $this->webhookHealthColor === 'success',
                                    'text-yellow-600 dark:text-yellow-400' => $this->webhookHealthColor === 'warning',
                                    'text-red-600 dark:text-red-400' => $this->webhookHealthColor === 'danger',
                                    'text-gray-600 dark:text-gray-400' => $this->webhookHealthColor === 'gray',
                                ]) />
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" @click.prevent="activeTab = 'webhooks'" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                View webhooks →
                            </a>
                        </div>
                    </div>
                </div>
                
                {{-- Recent Activity --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Recent Calls (24h)</p>
                                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                                    {{ $metrics['recent_calls_count'] }}
                                </p>
                                @if($metrics['failed_webhooks_count'] > 0)
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                        {{ $metrics['failed_webhooks_count'] }} failed webhooks
                                    </p>
                                @endif
                            </div>
                            <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-full">
                                <x-heroicon-m-phone-arrow-up-right class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="#" @click.prevent="activeTab = 'debugging'" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300">
                                View logs →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Recent Calls Widget --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Calls</h3>
                </div>
                <div class="p-6">
                    @if($this->recentCalls->count() > 0)
                        <div class="space-y-4">
                            @foreach($this->recentCalls as $call)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                    <div class="flex items-center gap-4">
                                        <div @class([
                                            'w-10 h-10 rounded-full flex items-center justify-center',
                                            'bg-green-100 dark:bg-green-900/20' => $call->call_status === 'completed',
                                            'bg-red-100 dark:bg-red-900/20' => $call->call_status === 'failed',
                                            'bg-yellow-100 dark:bg-yellow-900/20' => $call->call_status === 'in_progress',
                                        ])>
                                            <x-heroicon-m-phone @class([
                                                'w-5 h-5',
                                                'text-green-600 dark:text-green-400' => $call->call_status === 'completed',
                                                'text-red-600 dark:text-red-400' => $call->call_status === 'failed',
                                                'text-yellow-600 dark:text-yellow-400' => $call->call_status === 'in_progress',
                                            ]) />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ $call->from_number ?? 'Unknown' }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $call->created_at->diffForHumans() }} • {{ $call->duration_minutes ?? 0 }} min
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($call->appointment_id)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                                <x-heroicon-m-check-circle class="w-3 h-3 mr-1" />
                                                Appointment booked
                                            </span>
                                        @endif
                                        <x-filament::button
                                            size="sm"
                                            icon="heroicon-m-eye"
                                            color="gray"
                                            href="{{ route('filament.admin.resources.calls.view', $call) }}"
                                        >
                                            View
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-phone class="w-12 h-12 mx-auto text-gray-400" />
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent calls</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Agents Tab --}}
        <div x-show="activeTab === 'agents'" x-transition>
            <div class="mb-4 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Retell Agents</h2>
                <x-filament::button
                    wire:click="openAgentModal"
                    icon="heroicon-m-plus"
                >
                    Add Agent
                </x-filament::button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($this->activeAgents as $agent)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $agent->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $agent->agent_id }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($agent->active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="mt-4 space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <x-heroicon-m-link class="w-4 h-4 text-gray-400" />
                                    <span class="text-gray-600 dark:text-gray-300">{{ $agent->settings['webhook_url'] ?? 'Not configured' }}</span>
                                </div>
                                
                                @if($agent->phoneNumber)
                                    <div class="flex items-center gap-2 text-sm">
                                        <x-heroicon-m-phone class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600 dark:text-gray-300">{{ $agent->phoneNumber->number }}</span>
                                    </div>
                                @endif
                                
                                <div class="flex items-center gap-2 text-sm">
                                    <x-heroicon-m-calendar class="w-4 h-4 text-gray-400" />
                                    <span class="text-gray-600 dark:text-gray-300">
                                        Events: {{ implode(', ', $agent->settings['webhook_events'] ?? []) }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex gap-2">
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    wire:click="openAgentModal('{{ $agent->id }}')"
                                    icon="heroicon-m-pencil"
                                >
                                    Edit
                                </x-filament::button>
                                
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    wire:click="testAgent('{{ $agent->id }}')"
                                    icon="heroicon-m-beaker"
                                >
                                    Test
                                </x-filament::button>
                                
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    @click="showAgentDetails = '{{ $agent->id }}'"
                                    icon="heroicon-m-eye"
                                >
                                    Details
                                </x-filament::button>
                            </div>
                        </div>
                        
                        {{-- Agent Details Dropdown --}}
                        <div x-show="showAgentDetails === '{{ $agent->id }}'"
                             x-transition
                             class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Configuration Details</h4>
                            <pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto">{{ json_encode($agent->settings, JSON_PRETTY_PRINT) }}</pre>
                            
                            <div class="mt-3 flex justify-end">
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    @click="showAgentDetails = null"
                                >
                                    Close
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($this->activeAgents->count() === 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-12 text-center">
                        <x-heroicon-o-user-group class="w-12 h-12 mx-auto text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No agents configured</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new agent.</p>
                        <div class="mt-6">
                            <x-filament::button
                                wire:click="openAgentModal"
                                icon="heroicon-m-plus"
                            >
                                Add Your First Agent
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Phone Numbers Tab --}}
        <div x-show="activeTab === 'phone-numbers'" x-transition>
            <div class="mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Phone Number Configuration</h2>
            </div>
            
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Phone Number
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Agent
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Webhook URL
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            $phoneNumbers = \App\Models\PhoneNumber::where('company_id', auth()->user()->company_id)->get();
                        @endphp
                        
                        @foreach($phoneNumbers as $phoneNumber)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $phoneNumber->number }}
                                    </div>
                                    @if($phoneNumber->branch)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $phoneNumber->branch->name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($phoneNumber->retell_agent_id)
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            {{ $phoneNumber->retell_agent_id }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Not configured</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white truncate max-w-xs">
                                        {{ $phoneNumber->webhook_url ?? 'Default' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($phoneNumber->retell_agent_id)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                            Connected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                            Not Connected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <x-filament::button
                                        size="sm"
                                        color="gray"
                                        href="{{ route('filament.admin.resources.phone-numbers.edit', $phoneNumber) }}"
                                    >
                                        Configure
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Webhooks Tab --}}
        <div x-show="activeTab === 'webhooks'" x-transition>
            <div class="mb-4 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Webhooks</h2>
                <div class="flex gap-2">
                    <x-filament::button
                        color="gray"
                        size="sm"
                        wire:click="$refresh"
                        icon="heroicon-m-arrow-path"
                    >
                        Refresh
                    </x-filament::button>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                {{ $this->table }}
            </div>
        </div>
        
        {{-- Testing Tab --}}
        <div x-show="activeTab === 'testing'" x-transition>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Webhook Test --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Webhook Test</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Test your webhook configuration by sending a sample webhook payload.
                        </p>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook URL</label>
                                <input type="text" 
                                       value="{{ $webhookData['webhook_url'] ?? '' }}" 
                                       readonly 
                                       class="mt-1 block w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md text-sm">
                            </div>
                            
                            <x-filament::button
                                wire:click="testWebhook"
                                icon="heroicon-m-paper-airplane"
                                class="w-full"
                            >
                                Send Test Webhook
                            </x-filament::button>
                        </div>
                        
                        @if($testResults && isset($testResults['webhook']))
                            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Test Results</h4>
                                <dl class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Status:</dt>
                                        <dd class="font-medium {{ $testResults['success'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $testResults['success'] ? 'Success' : 'Failed' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500 dark:text-gray-400">Response Time:</dt>
                                        <dd class="font-medium text-gray-900 dark:text-white">{{ $testResults['response_time_ms'] ?? 'N/A' }}ms</dd>
                                    </div>
                                </dl>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Call Simulation --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Call Simulation</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Simulate a test call to verify the complete flow.
                        </p>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Scenario</label>
                                <select class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md text-sm">
                                    <option>Appointment Booking</option>
                                    <option>General Inquiry</option>
                                    <option>Cancellation Request</option>
                                </select>
                            </div>
                            
                            <x-filament::button
                                wire:click="simulateCall"
                                icon="heroicon-m-phone"
                                class="w-full"
                            >
                                Start Simulation
                            </x-filament::button>
                        </div>
                    </div>
                </div>
                
                {{-- Custom Functions Test --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 lg:col-span-2">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Custom Functions</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($customFunctions as $function)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $function['name'] }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $function['description'] ?? 'No description' }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if($function['enabled'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                                    Enabled
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                                    Disabled
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <x-filament::button
                                            size="sm"
                                            color="gray"
                                            wire:click="testCustomFunction('{{ $function['name'] }}')"
                                            icon="heroicon-m-play"
                                            class="w-full"
                                        >
                                            Test Function
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Debugging Tab --}}
        <div x-show="activeTab === 'debugging'" x-transition>
            <div class="space-y-6">
                {{-- Error Logs --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Errors</h3>
                    </div>
                    <div class="p-6">
                        @php
                            $recentErrors = \App\Models\RetellWebhook::where('company_id', auth()->user()->company_id)
                                ->where('status', 'failed')
                                ->latest()
                                ->limit(5)
                                ->get();
                        @endphp
                        
                        @if($recentErrors->count() > 0)
                            <div class="space-y-4">
                                @foreach($recentErrors as $error)
                                    <div class="border border-red-200 dark:border-red-800 rounded-lg p-4 bg-red-50 dark:bg-red-900/20">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <p class="font-medium text-red-900 dark:text-red-100">
                                                    {{ $error->event_type }} - {{ $error->created_at->format('Y-m-d H:i:s') }}
                                                </p>
                                                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                                    {{ $error->error_message ?? 'Unknown error' }}
                                                </p>
                                            </div>
                                            <x-filament::button
                                                size="xs"
                                                color="gray"
                                                @click="$wire.dispatch('showWebhookDetails', { webhook: {{ $error->toJson() }} })"
                                            >
                                                Details
                                            </x-filament::button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-green-400" />
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent errors</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- API Response Viewer --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">API Response Viewer</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            View raw API responses for debugging purposes.
                        </p>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Endpoint</label>
                                <select class="mt-1 block w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md text-sm">
                                    <option>GET /api/agents</option>
                                    <option>GET /api/calls</option>
                                    <option>POST /api/webhook</option>
                                </select>
                            </div>
                            
                            <x-filament::button
                                color="gray"
                                icon="heroicon-m-arrow-path"
                                class="w-full"
                            >
                                Fetch Response
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Agent Modal --}}
    <x-filament::modal 
        id="agent-modal" 
        :visible="$showAgentModal"
        width="lg"
    >
        <x-slot name="heading">
            {{ $selectedAgentId ? 'Edit Agent' : 'Create Agent' }}
        </x-slot>
        
        <form wire:submit="saveAgent">
            {{ $this->agentForm }}
            
            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button
                    color="gray"
                    @click="$wire.showAgentModal = false"
                >
                    Cancel
                </x-filament::button>
                
                <x-filament::button
                    type="submit"
                >
                    {{ $selectedAgentId ? 'Update' : 'Create' }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
    
    {{-- Webhook Details Modal --}}
    <x-filament::modal 
        id="webhook-details-modal" 
        width="2xl"
        x-data="{ webhook: null }"
        @open-webhook-details-modal.window="webhook = $event.detail.webhook"
    >
        <x-slot name="heading">
            Webhook Details
        </x-slot>
        
        <div class="space-y-4">
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Payload</h4>
                <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded overflow-x-auto" x-text="webhook ? JSON.stringify(webhook.payload, null, 2) : ''"></pre>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Response</h4>
                <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded overflow-x-auto" x-text="webhook ? JSON.stringify(webhook.response, null, 2) : ''"></pre>
            </div>
        </div>
    </x-filament::modal>
    
    @push('scripts')
    <script>
        // Auto-refresh metrics every 30 seconds
        setInterval(() => {
            @this.call('refreshMetrics');
        }, 30000);
    </script>
    @endpush
</x-filament-panels::page>