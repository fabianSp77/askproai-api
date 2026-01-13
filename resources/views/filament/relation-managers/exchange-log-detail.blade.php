<div class="space-y-6">
    {{-- Export Actions --}}
    <div class="p-4 bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 rounded-lg">
        <p class="text-xs text-primary-600 dark:text-primary-400 uppercase tracking-wider font-semibold mb-3">Export für Partner</p>
        @include('filament.components.exchange-log-export-buttons', ['log' => $log])
    </div>

    {{-- Header with Status --}}
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div class="flex items-center gap-4">
            @if($log->isSuccessful())
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-success-100 dark:bg-success-900">
                    <x-heroicon-o-check-circle class="w-8 h-8 text-success-600 dark:text-success-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-success-600 dark:text-success-400">Erfolgreich</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">HTTP {{ $log->status_code }}</p>
                </div>
            @else
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-danger-100 dark:bg-danger-900">
                    <x-heroicon-o-x-circle class="w-8 h-8 text-danger-600 dark:text-danger-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-danger-600 dark:text-danger-400">Fehlgeschlagen</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $log->error_class ?? 'HTTP ' . $log->status_code }}
                    </p>
                </div>
            @endif
        </div>

        <div class="text-right">
            @if($log->is_test)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-info-100 text-info-700 dark:bg-info-900 dark:text-info-300">
                    <x-heroicon-o-beaker class="w-3 h-3" />
                    Test Webhook
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300">
                    <x-heroicon-o-paper-airplane class="w-3 h-3" />
                    Echte Zustellung
                </span>
            @endif
        </div>
    </div>

    {{-- Meta Information --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Zeit</p>
            <p class="font-medium text-gray-900 dark:text-white">{{ $log->created_at->format('d.m.Y H:i:s') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</p>
        </div>

        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dauer</p>
            <p class="font-medium text-gray-900 dark:text-white">{{ $log->formatted_duration }}</p>
        </div>

        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Versuch</p>
            <p class="font-medium text-gray-900 dark:text-white">{{ $log->attempt_no }} / {{ $log->max_attempts }}</p>
        </div>

        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Event ID</p>
            <p class="font-mono text-xs text-gray-900 dark:text-white truncate" title="{{ $log->event_id }}">
                {{ Str::limit($log->event_id, 20) }}
            </p>
        </div>
    </div>

    {{-- Endpoint --}}
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Endpoint</p>
        <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                {{ $log->http_method }}
            </span>
            <code class="flex-1 font-mono text-sm text-gray-900 dark:text-white break-all">
                {{ $log->endpoint }}
            </code>
        </div>
    </div>

    {{-- Error Message (if present) --}}
    @if($log->error_message)
        <div class="p-4 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-700 rounded-lg">
            <p class="text-xs text-danger-600 dark:text-danger-400 uppercase tracking-wider mb-1">Fehlermeldung</p>
            <p class="font-mono text-sm text-danger-700 dark:text-danger-300">{{ $log->error_message }}</p>
        </div>
    @endif

    {{-- Request/Response Tabs --}}
    <div x-data="{ activeTab: 'request' }" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <button
                type="button"
                @click.stop="activeTab = 'request'"
                :class="activeTab === 'request' ? 'bg-white dark:bg-gray-900 border-b-2 border-primary-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
            >
                Request Payload
            </button>
            <button
                type="button"
                @click.stop="activeTab = 'response'"
                :class="activeTab === 'response' ? 'bg-white dark:bg-gray-900 border-b-2 border-primary-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
            >
                Response
            </button>
            <button
                type="button"
                @click.stop="activeTab = 'headers'"
                :class="activeTab === 'headers' ? 'bg-white dark:bg-gray-900 border-b-2 border-primary-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
                class="flex-1 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
            >
                Headers
            </button>
        </div>

        <div class="p-4 bg-gray-900 max-h-96 overflow-auto">
            {{-- Request Tab --}}
            <div x-show="activeTab === 'request'" x-cloak>
                @if($log->request_body_redacted)
                    <pre class="text-sm text-gray-100 font-mono whitespace-pre-wrap break-words">{{ json_encode($log->request_body_redacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="text-gray-400 italic">Kein Request Body verfügbar</p>
                @endif
            </div>

            {{-- Response Tab --}}
            <div x-show="activeTab === 'response'" x-cloak>
                @if($log->response_body_redacted)
                    <pre class="text-sm text-gray-100 font-mono whitespace-pre-wrap break-words">{{ json_encode($log->response_body_redacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="text-gray-400 italic">Keine Response verfügbar</p>
                @endif
            </div>

            {{-- Headers Tab --}}
            <div x-show="activeTab === 'headers'" x-cloak>
                @if($log->headers_redacted)
                    <pre class="text-sm text-gray-100 font-mono whitespace-pre-wrap break-words">{{ json_encode($log->headers_redacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="text-gray-400 italic">Keine Headers verfügbar</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Related Case/Call Info --}}
    @if($log->service_case_id || $log->call_id)
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Verknüpfungen</p>
            <div class="flex flex-wrap gap-2">
                @if($log->serviceCase)
                    <a href="{{ route('filament.admin.resources.service-cases.view', $log->service_case_id) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <x-heroicon-o-ticket class="w-4 h-4" />
                        <span>{{ $log->serviceCase->subject ?? 'Service Case #' . $log->service_case_id }}</span>
                    </a>
                @endif

                @if($log->call_id)
                    <a href="{{ route('filament.admin.resources.calls.view', $log->call_id) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <x-heroicon-o-phone class="w-4 h-4" />
                        <span>Call #{{ $log->call_id }}</span>
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
