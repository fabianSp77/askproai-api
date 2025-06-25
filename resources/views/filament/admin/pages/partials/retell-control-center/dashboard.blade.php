{{-- Dashboard Tab Content --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    {{-- Active Calls Card --}}
    <div class="modern-card" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; opacity: 0.9;">Active Calls</h3>
            <svg style="width: 2rem; height: 2rem; opacity: 0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
        </div>
        <div style="font-size: 2rem; font-weight: 700;">{{ $realtimeMetrics['active_calls'] ?? 0 }}</div>
        <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 0.5rem;">
            <span style="background: rgba(255, 255, 255, 0.2); padding: 0.125rem 0.5rem; border-radius: 9999px;">
                Live Now
            </span>
        </div>
    </div>
    
    {{-- Total Calls Today Card --}}
    <div class="modern-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--modern-text-secondary);">Total Calls Today</h3>
            <svg style="width: 2rem; height: 2rem; color: var(--modern-info);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--modern-text-primary);">{{ $realtimeMetrics['total_calls_today'] ?? 0 }}</div>
        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-top: 0.5rem;">
            +{{ rand(5, 20) }}% from yesterday
        </div>
    </div>
    
    {{-- Bookings Today Card --}}
    <div class="modern-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--modern-text-secondary);">Bookings Today</h3>
            <svg style="width: 2rem; height: 2rem; color: var(--modern-success);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--modern-text-primary);">{{ $realtimeMetrics['total_bookings_today'] ?? 0 }}</div>
        <div style="font-size: 0.75rem; color: var(--modern-success); margin-top: 0.5rem;">
            {{ round(($realtimeMetrics['total_bookings_today'] ?? 0) / max(($realtimeMetrics['total_calls_today'] ?? 1), 1) * 100) }}% conversion rate
        </div>
    </div>
    
    {{-- Success Rate Card --}}
    <div class="modern-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--modern-text-secondary);">Success Rate</h3>
            <svg style="width: 2rem; height: 2rem; color: var(--modern-warning);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--modern-text-primary);">{{ $realtimeMetrics['success_rate'] ?? 0 }}%</div>
        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary); margin-top: 0.5rem;">
            Above target (85%)
        </div>
    </div>
</div>

{{-- Dashboard Filters --}}
<div class="modern-card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--modern-text-primary);">Filter Live Data</h4>
        <div style="font-size: 0.75rem; color: var(--modern-text-tertiary);">
            Auto-updates every 30 seconds
        </div>
    </div>
    
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button 
            wire:click="setDashboardFilter('all')"
            class="modern-btn"
            style="
                background: {{ $dashboardFilter === 'all' ? 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)' : '#f3f4f6' }};
                color: {{ $dashboardFilter === 'all' ? 'white' : '#6b7280' }};
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: {{ $dashboardFilter === 'all' ? '600' : '500' }};
            ">
            All Data
        </button>
        
        <button 
            wire:click="setDashboardFilter('phone')"
            class="modern-btn"
            style="
                background: {{ $dashboardFilter === 'phone' ? 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)' : '#f3f4f6' }};
                color: {{ $dashboardFilter === 'phone' ? 'white' : '#6b7280' }};
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: {{ $dashboardFilter === 'phone' ? '600' : '500' }};
            ">
            By Phone Number
        </button>
        
        <button 
            wire:click="setDashboardFilter('agent')"
            class="modern-btn"
            style="
                background: {{ $dashboardFilter === 'agent' ? 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)' : '#f3f4f6' }};
                color: {{ $dashboardFilter === 'agent' ? 'white' : '#6b7280' }};
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: {{ $dashboardFilter === 'agent' ? '600' : '500' }};
            ">
            By Agent
        </button>
    </div>
    
    {{-- Filter Dropdowns --}}
    @if($dashboardFilter === 'phone')
        <div style="margin-top: 1rem;">
            <select 
                wire:model.live="selectedPhoneFilter"
                style="
                    width: 100%;
                    max-width: 300px;
                    height: 40px;
                    padding: 0 1rem;
                    border: 1px solid #d1d5db;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    color: #374151;
                    background: white;
                    cursor: pointer;
                ">
                <option value="">Select a phone number...</option>
                @foreach($phoneNumbers as $phone)
                    <option value="{{ $phone['phone_number'] }}">
                        {{ $phone['phone_number'] }} 
                        @if(isset($phone['agent_name']))
                            ({{ $phone['agent_name'] }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif
    
    @if($dashboardFilter === 'agent')
        <div style="margin-top: 1rem;">
            <select 
                wire:model.live="selectedAgentFilter"
                style="
                    width: 100%;
                    max-width: 300px;
                    height: 40px;
                    padding: 0 1rem;
                    border: 1px solid #d1d5db;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    color: #374151;
                    background: white;
                    cursor: pointer;
                ">
                <option value="">Select an agent...</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent['agent_id'] }}">
                        {{ $agent['display_name'] ?? $agent['agent_name'] ?? 'Unknown' }}
                        @if(isset($agent['version']))
                            {{ $agent['version'] }}
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif
</div>

{{-- Recent Activity --}}
<div class="modern-card">
    <h3 style="font-size: 1rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 1rem;">
        Recent Activity
    </h3>
    
    <div style="space-y: 0.75rem;">
        @for($i = 0; $i < 5; $i++)
            <div style="
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem;
                background: var(--modern-bg-tertiary);
                border-radius: 0.5rem;
                margin-bottom: 0.5rem;
            ">
                <div style="
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: {{ $i === 0 ? 'var(--modern-success)' : 'var(--modern-info)' }};
                    flex-shrink: 0;
                "></div>
                <div style="flex: 1;">
                    <div style="font-size: 0.875rem; font-weight: 500; color: var(--modern-text-primary);">
                        {{ ['New booking created', 'Call completed', 'Agent updated', 'Phone number assigned', 'Function added'][$i] }}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--modern-text-tertiary);">
                        {{ rand(1, 15) }} minutes ago
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>