{{-- Webhooks Tab Content --}}
<div style="display: grid; gap: 1.5rem;">
    {{-- Webhook Configuration --}}
    <div class="modern-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin: 0;">
                Webhook Configuration
            </h3>
            <span style="
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.375rem 0.75rem;
                border-radius: 0.375rem;
                font-size: 0.75rem;
                font-weight: 500;
                background: #d1fae5; 
                color: #065f46;
            ">
                <svg style="width: 0.875rem; height: 0.875rem;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Active
            </span>
        </div>
        
        <div style="display: grid; gap: 1rem;">
            {{-- Webhook URL --}}
            <div style="display: flex; justify-content: space-between; padding: 0.875rem; background: #f9fafb; border-radius: 0.375rem; align-items: center;">
                <div>
                    <span style="font-weight: 500; display: block; margin-bottom: 0.25rem;">Webhook URL</span>
                    <code style="font-size: 0.875rem; color: var(--modern-text-secondary);">https://api.askproai.de/api/retell/webhook</code>
                </div>
                <button 
                    onclick="navigator.clipboard.writeText('https://api.askproai.de/api/retell/webhook'); this.textContent = '✓ Copied'; setTimeout(() => this.textContent = 'Copy', 2000);"
                    class="modern-btn modern-btn-secondary"
                    style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">
                    Copy
                </button>
            </div>
            
            {{-- Event Types --}}
            <div style="padding: 0.875rem; background: #f9fafb; border-radius: 0.375rem;">
                <span style="font-weight: 500; display: block; margin-bottom: 0.75rem;">Subscribed Events</span>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @foreach(['call_started', 'call_ended', 'call_analyzed', 'voicemail_left'] as $event)
                        <span style="
                            display: inline-block;
                            padding: 0.25rem 0.75rem;
                            background: white;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.375rem;
                            font-size: 0.75rem;
                            font-weight: 500;
                            color: var(--modern-text-primary);
                        ">
                            {{ $event }}
                        </span>
                    @endforeach
                </div>
            </div>
            
            {{-- Test Webhook --}}
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <button 
                    wire:click="testWebhook"
                    wire:loading.attr="disabled"
                    class="modern-btn modern-btn-primary">
                    <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span wire:loading.remove>Send Test Webhook</span>
                    <span wire:loading>Sending...</span>
                </button>
                
                @if($webhookTestResult)
                    <span style="
                        font-size: 0.875rem; 
                        color: {{ $webhookTestResult['success'] ? '#10b981' : '#ef4444' }};
                        font-weight: 500;
                    ">
                        {{ $webhookTestResult['message'] }}
                    </span>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Recent Webhook Events --}}
    <div class="modern-card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin: 0;">
                Recent Webhook Events
            </h3>
            <button 
                wire:click="loadWebhookLogs"
                wire:loading.attr="disabled"
                class="modern-btn modern-btn-secondary"
                style="font-size: 0.75rem; padding: 0.375rem 0.75rem;">
                <svg style="width: 0.875rem; height: 0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.class="loading-spinner">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span wire:loading.remove style="margin-left: 0.25rem;">Refresh</span>
            </button>
        </div>
        
        <div style="max-height: 500px; overflow-y: auto;">
            @if(count($webhookLogs ?? []) > 0)
                <div style="display: grid; gap: 0.5rem;">
                    @foreach($webhookLogs as $log)
                        <div style="
                            padding: 1rem;
                            background: #f9fafb;
                            border: 1px solid #e5e7eb;
                            border-radius: 0.5rem;
                            transition: all 0.2s ease;
                        "
                        onmouseover="this.style.borderColor='#d1d5db'; this.style.background='#f3f4f6';"
                        onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='#f9fafb';">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="
                                        display: inline-block;
                                        padding: 0.25rem 0.625rem;
                                        background: {{ $log['event_type'] === 'call_ended' ? '#d1fae5' : ($log['event_type'] === 'call_started' ? '#dbeafe' : '#fef3c7') }};
                                        color: {{ $log['event_type'] === 'call_ended' ? '#065f46' : ($log['event_type'] === 'call_started' ? '#1e40af' : '#92400e') }};
                                        border-radius: 0.25rem;
                                        font-size: 0.75rem;
                                        font-weight: 600;
                                    ">
                                        {{ str_replace('_', ' ', strtoupper($log['event_type'])) }}
                                    </span>
                                    <span style="font-size: 0.75rem; color: #6b7280;">
                                        @if($log['created_at'])
                                            {{ \Carbon\Carbon::parse($log['created_at'])->diffForHumans() }}
                                        @else
                                            Unknown time
                                        @endif
                                    </span>
                                </div>
                                <span style="
                                    font-size: 0.75rem;
                                    color: {{ $log['status'] === 'processed' ? '#10b981' : ($log['status'] === 'failed' ? '#ef4444' : '#f59e0b') }};
                                    font-weight: 500;
                                ">
                                    {{ ucfirst($log['status'] ?? 'pending') }}
                                </span>
                            </div>
                            
                            <div style="display: grid; gap: 0.375rem; font-size: 0.8125rem;">
                                @if(isset($log['call_id']))
                                    <div style="display: flex; gap: 0.5rem;">
                                        <span style="color: #6b7280;">Call ID:</span>
                                        <span style="font-family: monospace; color: var(--modern-text-primary);">{{ substr($log['call_id'], 0, 20) }}...</span>
                                    </div>
                                @endif
                                
                                @if(isset($log['phone_number']))
                                    <div style="display: flex; gap: 0.5rem;">
                                        <span style="color: #6b7280;">Phone:</span>
                                        <span style="font-weight: 500;">{{ $log['phone_number'] }}</span>
                                    </div>
                                @endif
                                
                                @if(isset($log['duration']))
                                    <div style="display: flex; gap: 0.5rem;">
                                        <span style="color: #6b7280;">Duration:</span>
                                        <span>{{ gmdate('H:i:s', $log['duration']) }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            @if(isset($log['error']))
                                <div style="
                                    margin-top: 0.5rem;
                                    padding: 0.5rem;
                                    background: #fee2e2;
                                    border-radius: 0.25rem;
                                    font-size: 0.75rem;
                                    color: #991b1b;
                                ">
                                    Error: {{ $log['error'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 3rem; color: var(--modern-text-tertiary);">
                    <svg style="width: 3rem; height: 3rem; margin: 0 auto 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p style="font-size: 0.875rem; font-weight: 500;">No webhook events yet</p>
                    <p style="font-size: 0.75rem; margin-top: 0.25rem;">Events will appear here when calls are made</p>
                </div>
            @endif
        </div>
    </div>
    
    {{-- Webhook Statistics --}}
    <div class="modern-card">
        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--modern-text-primary); margin-bottom: 1.5rem;">
            Webhook Statistics (Last 24 Hours)
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="text-align: center; padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--modern-primary);">
                    {{ $webhookStats['total'] ?? 0 }}
                </div>
                <div style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-top: 0.25rem;">
                    Total Events
                </div>
            </div>
            
            <div style="text-align: center; padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: 700; color: #10b981;">
                    {{ $webhookStats['success'] ?? 0 }}
                </div>
                <div style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-top: 0.25rem;">
                    Successful
                </div>
            </div>
            
            <div style="text-align: center; padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: 700; color: #ef4444;">
                    {{ $webhookStats['failed'] ?? 0 }}
                </div>
                <div style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-top: 0.25rem;">
                    Failed
                </div>
            </div>
            
            <div style="text-align: center; padding: 1.5rem; background: #f9fafb; border-radius: 0.5rem;">
                <div style="font-size: 2rem; font-weight: 700; color: var(--modern-secondary);">
                    {{ number_format($webhookStats['avg_response_time'] ?? 0, 0) }}ms
                </div>
                <div style="font-size: 0.875rem; color: var(--modern-text-secondary); margin-top: 0.25rem;">
                    Avg Response Time
                </div>
            </div>
        </div>
    </div>
</div>