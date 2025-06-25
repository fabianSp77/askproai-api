{{-- Calls Tab Content --}}
<div>
    {{-- Header with Sync Actions --}}
    <div style="
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    ">
        <div>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin: 0;">
                Call History
            </h3>
            <p style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-top: 0.25rem;">
                View and manage all calls from Retell
            </p>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            <button 
                wire:click="syncCalls('{{ $callsPeriod }}')"
                wire:loading.attr="disabled"
                class="modern-btn modern-btn-primary"
                style="
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    background: var(--modern-gradient-primary);
                    color: white;
                ">
                <svg wire:loading.remove style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg wire:loading style="width: 1rem; height: 1rem; animation: spin 1s linear infinite;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span wire:loading.remove>Sync Calls</span>
                <span wire:loading>Syncing...</span>
            </button>
            
            <button 
                wire:click="importMissingCalls"
                wire:loading.attr="disabled"
                class="modern-btn"
                style="
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                ">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
                Import Missing
            </button>
        </div>
    </div>
    
    {{-- Filters --}}
    <div class="modern-card" style="margin-bottom: 1.5rem;">
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        ">
            {{-- Period Filter --}}
            <div>
                <label style="
                    display: block;
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: var(--modern-text-secondary);
                    margin-bottom: 0.5rem;
                ">
                    Time Period
                </label>
                <select 
                    wire:model.live="callsPeriod"
                    class="modern-select"
                    style="width: 100%;">
                    <option value="1h">Last Hour</option>
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="all">All Time</option>
                </select>
            </div>
            
            {{-- Status Filter --}}
            <div>
                <label style="
                    display: block;
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: var(--modern-text-secondary);
                    margin-bottom: 0.5rem;
                ">
                    Status
                </label>
                <select 
                    wire:model.live="callsFilter"
                    class="modern-select"
                    style="width: 100%;">
                    <option value="all">All Calls</option>
                    <option value="successful">Successful</option>
                    <option value="failed">Failed</option>
                    <option value="bookings">With Bookings</option>
                </select>
            </div>
            
            {{-- Search --}}
            <div style="grid-column: span 2;">
                <label style="
                    display: block;
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: var(--modern-text-secondary);
                    margin-bottom: 0.5rem;
                ">
                    Search
                </label>
                <div class="search-input-container">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        type="text"
                        wire:model.live.debounce.300ms="callsSearch"
                        placeholder="Search by phone, call ID, or summary..."
                        class="search-input"
                        style="width: 100%;">
                </div>
            </div>
        </div>
    </div>
    
    {{-- Calls Table --}}
    <div class="modern-card" style="overflow-x: auto;">
        @if(count($calls) > 0)
            <table style="
                width: 100%;
                border-collapse: collapse;
                font-size: 0.875rem;
            ">
                <thead>
                    <tr style="border-bottom: 1px solid var(--modern-border);">
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--modern-text-secondary);">Time</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--modern-text-secondary);">From</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--modern-text-secondary);">To</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--modern-text-secondary);">Agent</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--modern-text-secondary);">Duration</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--modern-text-secondary);">Status</th>
                        <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--modern-text-secondary);">Booking</th>
                        <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: var(--modern-text-secondary);">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calls as $call)
                        <tr style="border-bottom: 1px solid var(--modern-bg-tertiary);">
                            <td style="padding: 0.75rem; color: var(--modern-text-primary);">
                                {{ $call['start_time'] }}
                            </td>
                            <td style="padding: 0.75rem;">
                                <div>
                                    <div style="color: var(--modern-text-primary); font-weight: 500;">
                                        {{ $call['phone_number'] ?? 'Unknown' }}
                                    </div>
                                    <div style="color: var(--modern-text-tertiary); font-size: 0.75rem;">
                                        {{ $call['customer_name'] }}
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; color: var(--modern-text-secondary);">
                                {{ $call['to_number'] ?? 'N/A' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                <div style="font-size: 0.75rem;">
                                    <div style="color: var(--modern-text-primary);">{{ $call['agent_name'] }}</div>
                                    <div style="color: var(--modern-text-tertiary);">{{ $call['branch_name'] }}</div>
                                </div>
                            </td>
                            <td style="padding: 0.75rem; color: var(--modern-text-secondary);">
                                {{ $call['duration_formatted'] }}
                            </td>
                            <td style="padding: 0.75rem;">
                                @php
                                    $statusColor = match($call['status']) {
                                        'completed' => 'var(--modern-success)',
                                        'failed', 'error' => 'var(--modern-error)',
                                        'abandoned' => 'var(--modern-warning)',
                                        default => 'var(--modern-text-tertiary)'
                                    };
                                @endphp
                                <span style="
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 0.25rem;
                                    padding: 0.25rem 0.75rem;
                                    background: {{ $statusColor }}20;
                                    color: {{ $statusColor }};
                                    border-radius: 9999px;
                                    font-size: 0.75rem;
                                    font-weight: 500;
                                ">
                                    <span style="
                                        width: 6px;
                                        height: 6px;
                                        background: {{ $statusColor }};
                                        border-radius: 50%;
                                    "></span>
                                    {{ ucfirst($call['status'] ?? 'unknown') }}
                                </span>
                            </td>
                            <td style="padding: 0.75rem; text-align: center;">
                                @if($call['has_booking'])
                                    <svg style="width: 1.25rem; height: 1.25rem; color: var(--modern-success);" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <span style="color: var(--modern-text-tertiary);">-</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <button 
                                        wire:click="viewCallDetails('{{ $call['call_id'] }}')"
                                        class="modern-btn-icon"
                                        title="View Details">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    
                                    @if($call['public_log_url'])
                                        <a 
                                            href="{{ $call['public_log_url'] }}"
                                            target="_blank"
                                            class="modern-btn-icon"
                                            title="View Log">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="
                text-align: center;
                padding: 3rem;
                color: var(--modern-text-tertiary);
            ">
                <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <p style="font-size: 1rem; margin-bottom: 0.5rem;">No calls found</p>
                <p style="font-size: 0.875rem;">Try syncing calls from Retell or adjusting your filters</p>
            </div>
        @endif
    </div>
</div>

{{-- Call Details Modal --}}
@if($showCallDetails && $selectedCall)
    <div 
        x-data="{ show: @entangle('showCallDetails') }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            padding: 1rem;
        ">
        <div 
            @click.away="$wire.closeCallDetails()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            style="
                background: white;
                border-radius: 0.75rem;
                max-width: 800px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: var(--modern-shadow-xl);
            ">
            
            {{-- Modal Header --}}
            <div style="
                padding: 1.5rem;
                border-bottom: 1px solid var(--modern-border);
                display: flex;
                align-items: center;
                justify-content: space-between;
            ">
                <h3 style="font-size: 1.125rem; font-weight: 600; margin: 0;">
                    Call Details
                </h3>
                <button 
                    @click="$wire.closeCallDetails()"
                    style="
                        background: none;
                        border: none;
                        padding: 0.5rem;
                        cursor: pointer;
                        color: var(--modern-text-tertiary);
                        transition: color 0.2s;
                    ">
                    <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            {{-- Modal Content --}}
            <div style="padding: 1.5rem;">
                {{-- Basic Info --}}
                <div style="
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 1rem;
                    margin-bottom: 2rem;
                ">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">Call ID</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $selectedCall['call_id'] }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">Status</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ ucfirst($selectedCall['status'] ?? 'unknown') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">From</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $selectedCall['phone_number'] ?? 'Unknown' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">To</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $selectedCall['to_number'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">Agent</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $selectedCall['agent_name'] }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">Branch</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $selectedCall['branch'] }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">Start Time</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $selectedCall['start_time'] ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-bottom: 0.25rem;">Duration</div>
                        <div style="font-size: 0.875rem; font-weight: 500;">{{ $selectedCall['duration'] }}</div>
                    </div>
                </div>
                
                {{-- Customer Info --}}
                @if($selectedCall['customer'])
                    <div style="
                        background: var(--modern-bg-secondary);
                        padding: 1rem;
                        border-radius: 0.5rem;
                        margin-bottom: 1.5rem;
                    ">
                        <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem;">Customer Information</h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <div>
                                <div style="font-size: 0.75rem; color: var(--modern-text-tertiary);">Name</div>
                                <div style="font-size: 0.875rem;">{{ $selectedCall['customer']['name'] }}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--modern-text-tertiary);">Email</div>
                                <div style="font-size: 0.875rem;">{{ $selectedCall['customer']['email'] ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: var(--modern-text-tertiary);">Phone</div>
                                <div style="font-size: 0.875rem;">{{ $selectedCall['customer']['phone'] }}</div>
                            </div>
                        </div>
                    </div>
                @endif
                
                {{-- Transcript Summary --}}
                @if($selectedCall['transcript_summary'])
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem;">Summary</h4>
                        <div style="
                            background: var(--modern-bg-secondary);
                            padding: 1rem;
                            border-radius: 0.5rem;
                            font-size: 0.875rem;
                            line-height: 1.5;
                        ">
                            {{ $selectedCall['transcript_summary'] }}
                        </div>
                    </div>
                @endif
                
                {{-- Appointments --}}
                @if(!empty($selectedCall['appointments']))
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem;">Appointments Created</h4>
                        @foreach($selectedCall['appointments'] as $apt)
                            <div style="
                                background: var(--modern-bg-secondary);
                                padding: 1rem;
                                border-radius: 0.5rem;
                                margin-bottom: 0.5rem;
                            ">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 500;">{{ $apt['service'] }}</div>
                                        <div style="font-size: 0.875rem; color: var(--modern-text-secondary);">
                                            {{ $apt['date'] }} at {{ $apt['time'] }}
                                        </div>
                                    </div>
                                    <span style="
                                        padding: 0.25rem 0.75rem;
                                        background: var(--modern-success)20;
                                        color: var(--modern-success);
                                        border-radius: 9999px;
                                        font-size: 0.75rem;
                                        font-weight: 500;
                                    ">
                                        {{ ucfirst($apt['status']) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                {{-- Actions --}}
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    @if($selectedCall['recording_url'])
                        <a 
                            href="{{ $selectedCall['recording_url'] }}"
                            target="_blank"
                            class="modern-btn">
                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            </svg>
                            Recording
                        </a>
                    @endif
                    
                    @if($selectedCall['public_log_url'])
                        <a 
                            href="{{ $selectedCall['public_log_url'] }}"
                            target="_blank"
                            class="modern-btn">
                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Full Log
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif