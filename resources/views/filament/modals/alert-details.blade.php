<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500">Alert Type</p>
            <p class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($alert->alert_type)) }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Severity</p>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if($alert->severity === 'critical') bg-red-100 text-red-800
                @elseif($alert->severity === 'warning') bg-yellow-100 text-yellow-800
                @else bg-blue-100 text-blue-800
                @endif">
                {{ ucfirst($alert->severity) }}
            </span>
        </div>
    </div>

    <div>
        <p class="text-sm font-medium text-gray-500">Title</p>
        <p class="mt-1 text-sm text-gray-900">{{ $alert->title }}</p>
    </div>

    <div>
        <p class="text-sm font-medium text-gray-500">Message</p>
        <p class="mt-1 text-sm text-gray-900">{{ $alert->message }}</p>
    </div>

    @if($alert->threshold_value || $alert->current_value)
    <div class="grid grid-cols-2 gap-4">
        @if($alert->threshold_value)
        <div>
            <p class="text-sm font-medium text-gray-500">Threshold</p>
            <p class="mt-1 text-sm text-gray-900">{{ $alert->threshold_value }}%</p>
        </div>
        @endif
        @if($alert->current_value)
        <div>
            <p class="text-sm font-medium text-gray-500">Current Value</p>
            <p class="mt-1 text-sm text-gray-900">{{ $alert->current_value }}</p>
        </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500">Status</p>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if($alert->status === 'sent') bg-green-100 text-green-800
                @elseif($alert->status === 'failed') bg-red-100 text-red-800
                @elseif($alert->status === 'acknowledged') bg-blue-100 text-blue-800
                @else bg-gray-100 text-gray-800
                @endif">
                {{ ucfirst($alert->status) }}
            </span>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Created</p>
            <p class="mt-1 text-sm text-gray-900">{{ $alert->created_at->format('M d, Y H:i') }}</p>
        </div>
    </div>

    @if($alert->sent_at)
    <div>
        <p class="text-sm font-medium text-gray-500">Sent At</p>
        <p class="mt-1 text-sm text-gray-900">{{ $alert->sent_at->format('M d, Y H:i') }}</p>
    </div>
    @endif

    @if($alert->acknowledged_at && $alert->acknowledged_by)
    <div>
        <p class="text-sm font-medium text-gray-500">Acknowledged</p>
        <p class="mt-1 text-sm text-gray-900">
            {{ $alert->acknowledged_at->format('M d, Y H:i') }} by {{ $alert->acknowledgedBy?->name ?? 'Unknown' }}
        </p>
    </div>
    @endif

    @if($alert->data && count($alert->data) > 0)
    <div>
        <p class="text-sm font-medium text-gray-500 mb-2">Additional Data</p>
        <div class="bg-gray-50 rounded-lg p-3">
            <pre class="text-xs text-gray-700">{{ json_encode($alert->data, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
    @endif
</div>