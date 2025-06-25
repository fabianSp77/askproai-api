{{-- Phones Tab Content --}}
<div>
    {{-- Phone Numbers List --}}
    @if(count($phoneNumbers) > 0)
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1rem;">
            @foreach($phoneNumbers as $phone)
                <div class="modern-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <h4 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary);">
                            {{ $phone['phone_number'] ?? 'Unknown Number' }}
                        </h4>
                        <span style="
                            display: inline-block;
                            padding: 0.25rem 0.75rem;
                            font-size: 0.75rem;
                            font-weight: 500;
                            border-radius: 9999px;
                            background: {{ isset($phone['agent_id']) ? '#d1fae5' : '#fee2e2' }};
                            color: {{ isset($phone['agent_id']) ? '#065f46' : '#991b1b' }};
                        ">
                            {{ isset($phone['agent_id']) ? 'Assigned' : 'Unassigned' }}
                        </span>
                    </div>
                    
                    @if(isset($phone['agent_id']) && isset($phone['agent_name']))
                        <div style="margin-bottom: 0.75rem;">
                            <div style="font-size: 0.875rem; color: var(--modern-text-secondary);">
                                <strong>Agent:</strong> {{ $phone['agent_name'] }}
                                @if(isset($phone['agent_version']))
                                    {{ $phone['agent_version'] }}
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <div style="
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                        padding-top: 0.75rem;
                        border-top: 1px solid var(--modern-border);
                    ">
                        <select 
                            wire:model.defer="phoneAgentAssignment.{{ str_replace(['+', '-', ' ', '(', ')'], '', $phone['phone_number']) }}"
                            style="
                                flex: 1;
                                height: 36px;
                                padding: 0 0.75rem;
                                border: 1px solid #d1d5db;
                                border-radius: 0.375rem;
                                font-size: 0.875rem;
                                color: #374151;
                                background: white;
                                cursor: pointer;
                                outline: none;
                            ">
                            <option value="">Select agent...</option>
                            @foreach($agents as $agent)
                                <option 
                                    value="{{ $agent['agent_id'] }}"
                                    {{ isset($phone['agent_id']) && $phone['agent_id'] === $agent['agent_id'] ? 'selected' : '' }}
                                >
                                    {{ $agent['display_name'] ?? $agent['agent_name'] ?? 'Unknown' }}
                                    @if(isset($agent['version']))
                                        {{ $agent['version'] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        
                        <button 
                            wire:click="assignAgentToPhone('{{ $phone['phone_number'] }}')"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75"
                            wire:target="assignAgentToPhone('{{ $phone['phone_number'] }}')"
                            style="
                                padding: 0.5rem 1rem;
                                font-size: 0.875rem;
                                font-weight: 500;
                                border-radius: 0.375rem;
                                background: var(--modern-gradient-primary);
                                color: white;
                                border: none;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                white-space: nowrap;
                                display: inline-flex;
                                align-items: center;
                                gap: 0.375rem;
                                min-width: 80px;
                                justify-content: center;
                            "
                            onmouseover="if(!this.disabled) { this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(99, 102, 241, 0.3)'; }"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <svg wire:loading wire:target="assignAgentToPhone('{{ $phone['phone_number'] }}')" 
                                 class="loading-spinner" 
                                 style="width: 1rem; height: 1rem;" 
                                 fill="none" 
                                 stroke="currentColor" 
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span wire:loading.remove wire:target="assignAgentToPhone('{{ $phone['phone_number'] }}')">Update</span>
                            <span wire:loading wire:target="assignAgentToPhone('{{ $phone['phone_number'] }}')">Updating...</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="modern-card" style="text-align: center; padding: 4rem;">
            <svg style="width: 4rem; height: 4rem; margin: 0 auto 1rem; color: var(--modern-text-tertiary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 0.5rem;">
                No Phone Numbers Found
            </h3>
            <p style="font-size: 0.875rem; color: var(--modern-text-secondary);">
                No phone numbers are configured in your Retell account
            </p>
        </div>
    @endif
</div>