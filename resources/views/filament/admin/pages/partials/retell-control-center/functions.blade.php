{{-- Functions Tab Content --}}
<div>
    {{-- Agent Selection --}}
    <div class="modern-card" style="margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1;">
                <label style="display: block; font-size: 0.875rem; font-weight: 500; color: var(--modern-text-secondary); margin-bottom: 0.5rem;">
                    Select Agent to Manage Functions
                </label>
                <select 
                    wire:model.live="selectedAgentId"
                    style="
                        width: 100%;
                        max-width: 400px;
                        height: 40px;
                        padding: 0 1rem;
                        border: 1px solid #d1d5db;
                        border-radius: 0.5rem;
                        font-size: 0.875rem;
                        color: #374151;
                        background: white;
                        cursor: pointer;
                        outline: none;
                        transition: all 0.2s ease;
                    "
                    onchange="this.style.borderColor='#6366f1'"
                    onblur="this.style.borderColor='#d1d5db'">
                    <option value="">Choose an agent...</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent['agent_id'] }}">
                            {{ $agent['display_name'] ?? $agent['agent_name'] ?? 'Unknown' }}
                            @if(isset($agent['version']))
                                {{ $agent['version'] }}
                            @endif
                            @if(isset($agent['is_active']) && $agent['is_active'])
                                (Active)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            
            @if($selectedAgentId)
                <button 
                    wire:click="openFunctionBuilder"
                    class="modern-btn modern-btn-primary">
                    <svg style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Function
                </button>
            @endif
        </div>
    </div>
    
    @if($selectedAgentId && $selectedAgent)
        {{-- Function Filters --}}
        <div style="
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            background-color: var(--modern-bg-primary);
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--modern-border);
            box-shadow: var(--modern-shadow-sm);
            flex-wrap: wrap;
        ">
            {{-- Search Functions --}}
            <div style="flex: 1; min-width: 250px;">
                <div class="search-input-container">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="functionSearchTerm"
                        placeholder="Search functions..."
                        class="search-input">
                </div>
            </div>
            
            {{-- Type Filter --}}
            <select 
                wire:model.live="functionTypeFilter"
                style="
                    height: 40px;
                    padding: 0 1rem;
                    border: 1px solid #d1d5db;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    color: #374151;
                    background: white;
                    cursor: pointer;
                    outline: none;
                ">
                <option value="">All Types</option>
                <option value="cal_com">Cal.com</option>
                <option value="database">Database</option>
                <option value="system">System</option>
                <option value="custom">Custom</option>
            </select>
            
            {{-- Refresh Button --}}
            <button 
                wire:click="loadAgentFunctions"
                class="modern-btn modern-btn-secondary">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
        
        {{-- Functions Grid --}}
        @if(count($agentFunctions) > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1rem;">
                @foreach($agentFunctions as $function)
                    <div class="modern-card" style="
                        border-left: 4px solid {{ 
                            $function['type'] === 'cal_com' ? '#3b82f6' : 
                            ($function['type'] === 'database' ? '#10b981' : 
                            ($function['type'] === 'system' ? '#f59e0b' : '#8b5cf6')) 
                        }};
                    ">
                        <div style="display: flex; align-items: start; justify-content: space-between; margin-bottom: 0.75rem;">
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 0.25rem;">
                                    {{ $function['name'] ?? 'Unnamed Function' }}
                                </h4>
                                <span style="
                                    display: inline-block;
                                    padding: 0.125rem 0.5rem;
                                    font-size: 0.75rem;
                                    font-weight: 500;
                                    border-radius: 9999px;
                                    background: {{ 
                                        $function['type'] === 'cal_com' ? '#dbeafe' : 
                                        ($function['type'] === 'database' ? '#d1fae5' : 
                                        ($function['type'] === 'system' ? '#fed7aa' : '#e9d5ff')) 
                                    }};
                                    color: {{ 
                                        $function['type'] === 'cal_com' ? '#1e40af' : 
                                        ($function['type'] === 'database' ? '#065f46' : 
                                        ($function['type'] === 'system' ? '#92400e' : '#6b21a8')) 
                                    }};
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $function['type'] ?? 'custom')) }}
                                </span>
                            </div>
                            
                            <div style="display: flex; gap: 0.25rem;">
                                <button 
                                    wire:click="editFunction('{{ $function['name'] }}')"
                                    style="
                                        padding: 0.375rem;
                                        background: transparent;
                                        border: none;
                                        color: var(--modern-text-tertiary);
                                        cursor: pointer;
                                        border-radius: 0.375rem;
                                        transition: all 0.2s ease;
                                    "
                                    onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.color='#6366f1'"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#9ca3af'"
                                    title="Edit function">
                                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                
                                <button 
                                    wire:click="testFunction('{{ $function['name'] }}')"
                                    style="
                                        padding: 0.375rem;
                                        background: transparent;
                                        border: none;
                                        color: var(--modern-text-tertiary);
                                        cursor: pointer;
                                        border-radius: 0.375rem;
                                        transition: all 0.2s ease;
                                    "
                                    onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.color='#10b981'"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#9ca3af'"
                                    title="Test function">
                                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                
                                <button 
                                    wire:click="deleteFunction('{{ $function['name'] }}')"
                                    wire:confirm="Are you sure you want to delete this function?"
                                    style="
                                        padding: 0.375rem;
                                        background: transparent;
                                        border: none;
                                        color: var(--modern-text-tertiary);
                                        cursor: pointer;
                                        border-radius: 0.375rem;
                                        transition: all 0.2s ease;
                                    "
                                    onmouseover="this.style.backgroundColor='#fef2f2'; this.style.color='#ef4444'"
                                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#9ca3af'"
                                    title="Delete function">
                                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <p style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-bottom: 0.75rem;">
                            {{ $function['description'] ?? 'No description available' }}
                        </p>
                        
                        @if(isset($function['url']))
                            <div style="font-size: 0.75rem; color: var(--modern-text-tertiary);">
                                <strong>URL:</strong> {{ $function['url'] }}
                            </div>
                        @endif
                        
                        @if(isset($function['method']))
                            <div style="font-size: 0.75rem; color: var(--modern-text-tertiary);">
                                <strong>Method:</strong> {{ strtoupper($function['method']) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="modern-card" style="text-align: center; padding: 3rem;">
                <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: var(--modern-text-tertiary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 0.5rem;">
                    No functions found
                </h3>
                <p style="font-size: 0.875rem; color: var(--modern-text-secondary);">
                    This agent doesn't have any functions yet. Add one to get started.
                </p>
            </div>
        @endif
    @else
        {{-- No Agent Selected --}}
        <div class="modern-card" style="text-align: center; padding: 4rem;">
            <svg style="width: 4rem; height: 4rem; margin: 0 auto 1rem; color: var(--modern-text-tertiary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 0.5rem;">
                Select an Agent
            </h3>
            <p style="font-size: 0.875rem; color: var(--modern-text-secondary);">
                Choose an agent from the dropdown above to manage its functions
            </p>
        </div>
    @endif
</div>