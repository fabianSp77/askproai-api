@props([
    'agent' => [],
    'globalState' => []
])

@php
$isSelected = isset($globalState['selectedAgentId']) && $globalState['selectedAgentId'] === ($agent['agent_id'] ?? '');
$metrics = $agent['metrics'] ?? [];
$performanceStatus = $metrics['status'] ?? 'good';
$statusColors = [
    'excellent' => ['bg' => '#10b981', 'text' => '#ffffff'],
    'good' => ['bg' => '#3b82f6', 'text' => '#ffffff'],
    'warning' => ['bg' => '#f59e0b', 'text' => '#ffffff'],
    'critical' => ['bg' => '#ef4444', 'text' => '#ffffff']
];
$currentStatusColor = $statusColors[$performanceStatus] ?? $statusColors['good'];
@endphp

<div style="
    background: #ffffff;
    border: 2px solid {{ $isSelected ? '#6366f1' : '#e5e7eb' }};
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    box-shadow: {{ $isSelected ? '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)' : '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)' }};
    min-height: 280px;
    display: flex;
    flex-direction: column;
"
     wire:click="selectAgent('{{ $agent['agent_id'] ?? '' }}')"
     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'; this.style.borderColor='#6366f1';"
     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='{{ $isSelected ? '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)' : '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)' }}'; this.style.borderColor='{{ $isSelected ? '#6366f1' : '#e5e7eb' }}';"
     x-data="{ 
         showVersionDropdown: false,
         selectedVersion: '{{ $agent['version'] ?? 'V1' }}',
         versions: {{ json_encode($agent['all_versions'] ?? []) }}
     }">
    
    {{-- Agent Header --}}
    <div style="margin-bottom: 20px;">
        {{-- Top Row: Status and Version --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 12px; min-height: 32px; flex-wrap: wrap;">
            {{-- Status Badge --}}
            <div style="display: flex; align-items: center; gap: 8px;">
                @if($agent['is_active'] ?? false)
                    <span style="
                        display: inline-flex;
                        align-items: center;
                        padding: 3px 10px;
                        border-radius: 9999px;
                        font-size: 11px;
                        font-weight: 500;
                        background-color: #dcfce7;
                        color: #166534;
                        white-space: nowrap;
                    ">
                        <svg style="width: 6px; height: 6px; margin-right: 4px;" fill="currentColor" viewBox="0 0 8 8">
                            <circle cx="4" cy="4" r="3"/>
                        </svg>
                        Active
                    </span>
                @else
                    <span style="
                        display: inline-flex;
                        align-items: center;
                        padding: 3px 10px;
                        border-radius: 9999px;
                        font-size: 11px;
                        font-weight: 500;
                        background-color: #f3f4f6;
                        color: #6b7280;
                        white-space: nowrap;
                    ">
                        <svg style="width: 6px; height: 6px; margin-right: 4px;" fill="currentColor" viewBox="0 0 8 8">
                            <circle cx="4" cy="4" r="3"/>
                        </svg>
                        Inactive
                    </span>
                @endif
                
                {{-- Sync Status --}}
                @if(isset($agent['sync_status']))
                    @php
                    $syncColors = [
                        'synced' => ['bg' => '#d1fae5', 'text' => '#065f46'],
                        'pending' => ['bg' => '#fed7aa', 'text' => '#92400e'],
                        'syncing' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b']
                    ];
                    $currentSyncColor = $syncColors[$agent['sync_status']] ?? $syncColors['pending'];
                    @endphp
                    <span style="
                        display: inline-flex;
                        align-items: center;
                        padding: 3px 10px;
                        border-radius: 9999px;
                        font-size: 11px;
                        font-weight: 500;
                        background-color: {{ $currentSyncColor['bg'] }};
                        color: {{ $currentSyncColor['text'] }};
                        white-space: nowrap;
                    " title="Last synced: {{ $agent['last_synced_at'] ?? 'Never' }}">
                        @if($agent['sync_status'] === 'syncing')
                            <svg class="loading-spinner" style="width: 10px; height: 10px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="60" stroke-dashoffset="0"></circle>
                            </svg>
                        @else
                            <svg style="width: 10px; height: 10px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        @endif
                        {{ ucfirst($agent['sync_status']) }}
                    </span>
                @endif
            </div>
            
            {{-- Version Selector --}}
            <div style="position: relative; margin-left: auto;" @click.stop>
                <button @click="showVersionDropdown = !showVersionDropdown"
                        style="
                            display: inline-flex;
                            align-items: center;
                            padding: 4px 10px;
                            font-size: 13px;
                            font-weight: 500;
                            border-radius: 6px;
                            background-color: #e0e7ff;
                            color: #4338ca;
                            border: none;
                            cursor: pointer;
                            transition: all 0.15s ease;
                            white-space: nowrap;
                        "
                        onmouseover="this.style.backgroundColor='#c7d2fe'"
                        onmouseout="this.style.backgroundColor='#e0e7ff'"
                        title="Select agent version">
                    <span x-text="selectedVersion">{{ $agent['version'] ?? 'V1' }}</span>
                    <svg style="margin-left: 4px; width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                {{-- Version Dropdown --}}
                <div x-show="showVersionDropdown" 
                     @click.away="showVersionDropdown = false"
                     x-transition
                     style="
                        position: absolute;
                        z-index: 20;
                        margin-top: 8px;
                        width: 200px;
                        border-radius: 8px;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                        background-color: white;
                        border: 1px solid #e5e7eb;
                        overflow: hidden;
                        right: 0;
                     ">
                    <div style="padding: 4px;">
                        @if($agent['total_versions'] ?? 0 > 1)
                            @for($i = ($agent['total_versions'] ?? 1); $i >= 1; $i--)
                                <button wire:click="selectAgentVersion('{{ $agent['base_name'] ?? '' }}', 'V{{ $i }}')"
                                        @click="selectedVersion = 'V{{ $i }}'; showVersionDropdown = false"
                                        style="
                                            width: 100%;
                                            text-align: left;
                                            padding: 8px 16px;
                                            font-size: 14px;
                                            color: #374151;
                                            background: none;
                                            border: none;
                                            cursor: pointer;
                                            display: flex;
                                            align-items: center;
                                            justify-content: space-between;
                                            border-radius: 4px;
                                            transition: background-color 0.15s ease;
                                        "
                                        onmouseover="this.style.backgroundColor='#f3f4f6'"
                                        onmouseout="this.style.backgroundColor='transparent'">
                                    <span>V{{ $i }}</span>
                                    @if(($agent['active_version']['version'] ?? '') === 'V' . $i)
                                        <span style="font-size: 12px; color: #10b981;">Active</span>
                                    @endif
                                </button>
                            @endfor
                        @else
                            <div style="padding: 8px 16px; font-size: 14px; color: #9ca3af;">No other versions</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Agent Name and ID --}}
        <div>
            <h3 style="
                font-size: 18px;
                font-weight: 600;
                color: #111827;
                margin: 0 0 4px 0;
                line-height: 1.2;
            ">
                {{ $agent['display_name'] ?? $agent['agent_name'] ?? 'Unknown Agent' }}
            </h3>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Agent ID: {{ substr($agent['agent_id'] ?? 'N/A', 0, 8) }}...
            </p>
        </div>
    </div>
    
    {{-- Real-time Metrics Grid --}}
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px; flex: 1;">
        {{-- Calls Today --}}
        <div style="background-color: #f9fafb; border-radius: 8px; padding: 12px; cursor: help;" 
             title="Number of calls handled by this agent today">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                <span style="font-size: 12px; color: #6b7280;">Calls Today</span>
                @if(($metrics['calls_trend'] ?? 0) > 0)
                    <svg style="width: 16px; height: 16px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                @elseif(($metrics['calls_trend'] ?? 0) < 0)
                    <svg style="width: 16px; height: 16px; color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                @endif
            </div>
            <div style="display: flex; align-items: baseline;">
                <span style="font-size: 24px; font-weight: 600; color: #111827;">{{ $metrics['calls_today'] ?? 0 }}</span>
                @if($metrics['calls_trend'] ?? 0)
                    <span style="font-size: 12px; color: {{ ($metrics['calls_trend'] ?? 0) > 0 ? '#10b981' : '#ef4444' }}; margin-left: 4px;">
                        {{ ($metrics['calls_trend'] ?? 0) > 0 ? '+' : '' }}{{ $metrics['calls_trend'] ?? 0 }}%
                    </span>
                @endif
            </div>
        </div>
        
        {{-- Success Rate --}}
        <div style="background-color: #f9fafb; border-radius: 8px; padding: 12px; cursor: help;"
             title="Percentage of successful call outcomes">
            <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Success Rate</span>
            <div style="display: flex; align-items: baseline;">
                <span style="font-size: 24px; font-weight: 600; color: #111827;">{{ $metrics['success_rate'] ?? 0 }}%</span>
            </div>
            <div style="margin-top: 8px; width: 100%; background-color: #e5e7eb; border-radius: 9999px; height: 6px; overflow: hidden;">
                <div style="background-color: #10b981; height: 100%; border-radius: 9999px; width: {{ $metrics['success_rate'] ?? 0 }}%; transition: width 0.3s ease;"></div>
            </div>
        </div>
        
        {{-- Average Duration --}}
        <div style="background-color: #f9fafb; border-radius: 8px; padding: 12px; cursor: help;"
             title="Average call duration for this agent">
            <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Avg. Duration</span>
            <span style="font-size: 24px; font-weight: 600; color: #111827;">{{ $metrics['avg_duration'] ?? '0:00' }}</span>
        </div>
        
        {{-- Performance Status --}}
        <div style="background-color: #f9fafb; border-radius: 8px; padding: 12px; cursor: help;"
             title="Overall performance rating based on multiple factors">
            <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Performance</span>
            <span style="
                display: inline-flex;
                align-items: center;
                padding: 4px 12px;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 500;
                background-color: {{ $currentStatusColor['bg'] }};
                color: {{ $currentStatusColor['text'] }};
            ">
                {{ ucfirst($performanceStatus) }}
            </span>
        </div>
    </div>
    
    {{-- Functions Info --}}
    @if(isset($agent['function_count']) && $agent['function_count'] > 0)
    <div style="margin-top: 12px; padding: 10px; background-color: #f3f4f6; border-radius: 8px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg style="width: 16px; height: 16px; color: #6366f1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                <span style="font-size: 13px; color: #4b5563; font-weight: 500;">
                    {{ $agent['function_count'] }} {{ $agent['function_count'] == 1 ? 'Function' : 'Functions' }}
                </span>
            </div>
            <button wire:click.stop="viewAgentFunctions('{{ $agent['agent_id'] ?? '' }}')"
                    style="
                        font-size: 12px;
                        color: #6366f1;
                        background: none;
                        border: none;
                        cursor: pointer;
                        text-decoration: underline;
                        padding: 2px 4px;
                    "
                    onmouseover="this.style.color='#4338ca'"
                    onmouseout="this.style.color='#6366f1'">
                View
            </button>
        </div>
    </div>
    @endif
    
    {{-- Quick Actions --}}
    <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #e5e7eb;">
        <div style="display: flex; align-items: center; gap: 8px;">
            {{-- Test Call Button --}}
            <button wire:click.stop="testCall('{{ $agent['agent_id'] ?? '' }}')"
                    style="
                        display: inline-flex;
                        align-items: center;
                        padding: 6px 12px;
                        font-size: 13px;
                        font-weight: 500;
                        border-radius: 6px;
                        background-color: #dbeafe;
                        color: #1e40af;
                        border: none;
                        cursor: pointer;
                        transition: all 0.15s ease;
                    "
                    onmouseover="this.style.backgroundColor='#bfdbfe'"
                    onmouseout="this.style.backgroundColor='#dbeafe'">
                <svg style="width: 14px; height: 14px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Test
            </button>
            
            {{-- Edit Button --}}
            <button wire:click.stop="openAgentEditor('{{ $agent['agent_id'] ?? '' }}')"
                    style="
                        display: inline-flex;
                        align-items: center;
                        padding: 6px 12px;
                        font-size: 13px;
                        font-weight: 500;
                        border-radius: 6px;
                        background-color: #f3f4f6;
                        color: #374151;
                        border: none;
                        cursor: pointer;
                        transition: all 0.15s ease;
                    "
                    onmouseover="this.style.backgroundColor='#e5e7eb'"
                    onmouseout="this.style.backgroundColor='#f3f4f6'">
                <svg style="width: 14px; height: 14px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </button>
            
            {{-- Performance Button --}}
            <button wire:click.stop="openPerformanceDashboard('{{ $agent['agent_id'] ?? '' }}')"
                    style="
                        display: inline-flex;
                        align-items: center;
                        padding: 6px 12px;
                        font-size: 13px;
                        font-weight: 500;
                        border-radius: 6px;
                        background-color: #ede9fe;
                        color: #5b21b6;
                        border: none;
                        cursor: pointer;
                        transition: all 0.15s ease;
                    "
                    onmouseover="this.style.backgroundColor='#ddd6fe'"
                    onmouseout="this.style.backgroundColor='#ede9fe'">
                <svg style="width: 14px; height: 14px; margin-right: 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics
            </button>
        </div>
        
        {{-- View Details --}}
        <button wire:click.stop="selectAgent('{{ $agent['agent_id'] ?? '' }}')"
                style="
                    font-size: 13px;
                    color: #4f46e5;
                    font-weight: 500;
                    background: none;
                    border: none;
                    cursor: pointer;
                    transition: color 0.15s ease;
                "
                onmouseover="this.style.color='#4338ca'"
                onmouseout="this.style.color='#4f46e5'">
            Details →
        </button>
    </div>
</div>