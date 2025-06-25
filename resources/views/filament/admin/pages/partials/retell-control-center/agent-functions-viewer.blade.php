{{-- Agent Functions Viewer Modal --}}
@if($showFunctionBuilder && $selectedAgent && $selectedAgentId)
<div style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    padding: 1rem;
" wire:click="$set('showFunctionBuilder', false)">
    <div style="
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    " wire:click.stop>
        {{-- Header --}}
        <div style="
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        ">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">
                        Functions for {{ $selectedAgent['display_name'] ?? 'Agent' }}
                    </h2>
                    <p style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.25rem;">
                        {{ count($agentFunctions) }} {{ count($agentFunctions) == 1 ? 'function' : 'functions' }} configured
                    </p>
                </div>
                <button 
                    wire:click="$set('showFunctionBuilder', false)"
                    style="
                        background: rgba(255, 255, 255, 0.2);
                        border: none;
                        padding: 0.5rem;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    "
                    onmouseover="this.style.backgroundColor='rgba(255, 255, 255, 0.3)'"
                    onmouseout="this.style.backgroundColor='rgba(255, 255, 255, 0.2)'">
                    <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        {{-- Content --}}
        <div style="padding: 1.5rem; overflow-y: auto; flex: 1;">
            @if(count($agentFunctions) > 0)
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1rem;">
                    @foreach($agentFunctions as $function)
                        <div style="
                            background: #f9fafb;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            padding: 1.25rem;
                            border-left: 4px solid {{ 
                                $function['type'] === 'cal_com' ? '#3b82f6' : 
                                ($function['type'] === 'database' ? '#10b981' : 
                                ($function['type'] === 'system' ? '#f59e0b' : '#8b5cf6')) 
                            }};
                        ">
                            <div style="margin-bottom: 0.75rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
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
                            
                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.75rem;">
                                {{ $function['description'] ?? 'No description available' }}
                            </p>
                            
                            @if(isset($function['url']))
                                <div style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.25rem;">
                                    <strong>URL:</strong> <code style="background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 0.25rem;">{{ $function['url'] }}</code>
                                </div>
                            @endif
                            
                            @if(isset($function['method']))
                                <div style="font-size: 0.75rem; color: #9ca3af;">
                                    <strong>Method:</strong> {{ strtoupper($function['method']) }}
                                </div>
                            @endif
                            
                            @if(isset($function['parameters']) && is_array($function['parameters']))
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb;">
                                    <div style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin-bottom: 0.5rem;">
                                        Parameters:
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        @foreach($function['parameters'] as $param)
                                            <span style="
                                                font-size: 0.7rem;
                                                padding: 0.125rem 0.375rem;
                                                background: #e5e7eb;
                                                color: #4b5563;
                                                border-radius: 0.25rem;
                                            ">
                                                {{ is_array($param) ? ($param['name'] ?? '') : $param }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 3rem;">
                    <svg style="width: 4rem; height: 4rem; margin: 0 auto 1rem; color: #d1d5db;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                        No Functions Found
                    </h3>
                    <p style="font-size: 0.875rem; color: #6b7280;">
                        This agent doesn't have any functions configured yet.
                    </p>
                </div>
            @endif
        </div>
        
        {{-- Footer --}}
        <div style="
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        ">
            <button 
                wire:click="$set('showFunctionBuilder', false)"
                style="
                    padding: 0.5rem 1.25rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    border-radius: 0.5rem;
                    background: white;
                    color: #374151;
                    border: 1px solid #d1d5db;
                    cursor: pointer;
                    transition: all 0.2s ease;
                "
                onmouseover="this.style.backgroundColor='#f9fafb'"
                onmouseout="this.style.backgroundColor='white'">
                Close
            </button>
            
            <a 
                href="https://retellai.com/dashboard/llm/{{ $selectedAgent['response_engine']['llm_id'] ?? '' }}"
                target="_blank"
                style="
                    padding: 0.5rem 1.25rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    border-radius: 0.5rem;
                    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                    color: white;
                    border: none;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
                "
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(99, 102, 241, 0.3)'"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                Edit in Retell
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>
</div>
@endif