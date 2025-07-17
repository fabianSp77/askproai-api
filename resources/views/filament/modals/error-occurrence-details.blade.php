<div class="space-y-4">
    <div>
        <h4 class="text-sm font-medium text-gray-700">Occurrence ID</h4>
        <p class="text-sm text-gray-900">#{{ $occurrence->id }}</p>
    </div>

    <div>
        <h4 class="text-sm font-medium text-gray-700">Timestamp</h4>
        <p class="text-sm text-gray-900">{{ $occurrence->created_at->format('M j, Y g:i:s A') }}</p>
    </div>

    @if($occurrence->company)
        <div>
            <h4 class="text-sm font-medium text-gray-700">Company</h4>
            <p class="text-sm text-gray-900">{{ $occurrence->company->name }}</p>
        </div>
    @endif

    @if($occurrence->user)
        <div>
            <h4 class="text-sm font-medium text-gray-700">User</h4>
            <p class="text-sm text-gray-900">{{ $occurrence->user->email }}</p>
        </div>
    @endif

    <div>
        <h4 class="text-sm font-medium text-gray-700">Environment</h4>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
            {{ $occurrence->environment === 'production' ? 'bg-red-100 text-red-800' : '' }}
            {{ $occurrence->environment === 'staging' ? 'bg-yellow-100 text-yellow-800' : '' }}
            {{ $occurrence->environment === 'local' ? 'bg-blue-100 text-blue-800' : '' }}">
            {{ ucfirst($occurrence->environment) }}
        </span>
    </div>

    @if($occurrence->request_url)
        <div>
            <h4 class="text-sm font-medium text-gray-700">Request URL</h4>
            <p class="text-sm text-gray-900 break-all">{{ $occurrence->request_method }} {{ $occurrence->request_url }}</p>
        </div>
    @endif

    @if($occurrence->context && count($occurrence->context) > 0)
        <div>
            <h4 class="text-sm font-medium text-gray-700">Context</h4>
            <pre class="text-xs bg-gray-100 p-2 rounded overflow-x-auto">{{ json_encode($occurrence->context, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if($occurrence->stack_trace)
        <div>
            <h4 class="text-sm font-medium text-gray-700">Stack Trace</h4>
            <pre class="text-xs bg-gray-900 text-gray-100 p-2 rounded overflow-x-auto max-h-48">{{ $occurrence->getTruncatedStackTrace(10) }}</pre>
        </div>
    @endif

    @if($occurrence->was_resolved)
        <div>
            <h4 class="text-sm font-medium text-gray-700">Resolution</h4>
            <p class="text-sm text-gray-900">
                Resolved at {{ $occurrence->resolved_at->format('M j, Y g:i:s A') }}
                ({{ $occurrence->getResolutionTimeForHumans() }})
            </p>
            @if($occurrence->solution)
                <p class="text-sm text-gray-600 mt-1">
                    Solution used: {{ $occurrence->solution->title }}
                </p>
            @endif
        </div>
    @endif
</div>