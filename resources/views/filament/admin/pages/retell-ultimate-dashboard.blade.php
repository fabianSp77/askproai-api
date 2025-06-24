<x-filament-panels::page>
    {{-- Modern CSS ohne externe Abhängigkeiten --}}
    <style>
        .modern-function-card {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        
        .modern-function-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.5);
        }
        
        .badge-gradient {
            padding: 6px 16px;
            border-radius: 24px;
            font-size: 13px;
            font-weight: 700;
            color: white;
            display: inline-block;
        }
        
        .badge-cal { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .badge-custom { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .badge-system { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #744210; }
    </style>

    <div class="space-y-6" x-data="{ 
        selectedTab: 'overview',
        selectedAgent: null,
        showFunctions: false
    }">
        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Retell Configuration</h2>
                <button 
                    wire:click="loadAgents"
                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
            
            @if($error)
                <div class="p-4 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg">
                    {{ $error }}
                </div>
            @endif
            
            @if($successMessage)
                <div class="p-4 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-lg">
                    {{ $successMessage }}
                </div>
            @endif
        </div>

        {{-- Agent Selection --}}
        @if(count($agents) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium mb-4">Select Agent</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($agents as $agent)
                        <button 
                            wire:click="selectAgent('{{ $agent['agent_id'] }}')"
                            @click="selectedAgent = '{{ $agent['agent_id'] }}'; showFunctions = true"
                            :class="selectedAgent === '{{ $agent['agent_id'] }}' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30' : 'border-gray-200 dark:border-gray-700'"
                            class="p-3 border-2 rounded-lg transition-all hover:shadow-md"
                        >
                            <div class="font-medium text-sm">{{ \Illuminate\Support\Str::afterLast($agent['agent_name'], '/') }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Functions Display --}}
        @if($selectedAgent && $llmData)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6" x-show="showFunctions">
                <h3 class="text-lg font-medium mb-4">Functions</h3>
                
                @if(isset($llmData['general_tools']) && count($llmData['general_tools']) > 0)
                    <div class="space-y-4">
                        @foreach($llmData['general_tools'] as $function)
                            <div class="modern-function-card">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">
                                            {{ $function['name'] }}
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                                            {{ $function['description'] ?? 'No description' }}
                                        </p>
                                        @php
                                            $type = $function['type'] ?? 'custom';
                                            $badgeClass = match(true) {
                                                str_contains($type, 'cal') => 'badge-cal',
                                                $type === 'end_call' || $type === 'system' => 'badge-system',
                                                default => 'badge-custom'
                                            };
                                            $badgeText = match(true) {
                                                str_contains($type, 'cal') => 'Cal.com',
                                                $type === 'end_call' || $type === 'system' => 'System',
                                                default => 'Custom'
                                            };
                                        @endphp
                                        <span class="badge-gradient {{ $badgeClass }}">
                                            {{ $badgeText }}
                                        </span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button 
                                            wire:click="editFunction('{{ $function['name'] }}')"
                                            class="p-2 text-gray-600 hover:text-primary-600 transition-colors"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No functions configured for this agent.</p>
                @endif
            </div>
        @endif
        
        {{-- Function Editor Modal --}}
        @if($editingFunction)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Edit Function: {{ $editingFunction }}</h3>
                            <button wire:click="cancelEditingFunction" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Function Name</label>
                                <input type="text" wire:model="functionEditor.name" disabled
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea wire:model="functionEditor.description" rows="3"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL</label>
                                <input type="text" wire:model="functionEditor.url"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">HTTP Method</label>
                                <select wire:model="functionEditor.method"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="functionEditor.speak_during_execution"
                                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Speak during execution</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="functionEditor.speak_after_execution"
                                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Speak after execution</span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Execution Message</label>
                                <input type="text" wire:model="functionEditor.execution_message"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button wire:click="cancelEditingFunction"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </button>
                            <button wire:click="saveFunction"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>