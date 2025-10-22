@if(count($alerts) > 0)
<div class="space-y-4">
    @foreach($alerts as $alert)
        <div class="rounded-lg border p-4
            @if($alert['type'] === 'critical') border-danger-500 bg-danger-50 dark:bg-danger-900/20 {{ $alert['priority'] === 1 ? 'ring-2 ring-danger-400' : '' }}
            @elseif($alert['type'] === 'high') border-warning-500 bg-warning-50 dark:bg-warning-900/20
            @elseif($alert['type'] === 'medium') border-info-500 bg-info-50 dark:bg-info-900/20
            @else border-gray-300 bg-gray-50 dark:bg-gray-900/20
            @endif">

            <div class="flex items-start gap-4">
                {{-- Icon --}}
                <div class="flex-shrink-0 text-3xl">
                    {{ $alert['icon'] }}
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    {{-- Title --}}
                    <h3 class="text-lg font-semibold mb-1.5
                        @if($alert['type'] === 'critical') text-danger-900 dark:text-danger-100
                        @elseif($alert['type'] === 'high') text-warning-900 dark:text-warning-100
                        @elseif($alert['type'] === 'medium') text-info-900 dark:text-info-100
                        @else text-gray-900 dark:text-gray-100
                        @endif">
                        {{ $alert['title'] }}
                    </h3>

                    {{-- Message --}}
                    <p class="text-sm mb-3
                        @if($alert['type'] === 'critical') text-danger-800 dark:text-danger-200
                        @elseif($alert['type'] === 'high') text-warning-800 dark:text-warning-200
                        @elseif($alert['type'] === 'medium') text-info-800 dark:text-info-200
                        @else text-gray-800 dark:text-gray-200
                        @endif">
                        {{ $alert['message'] }}
                    </p>

                    {{-- Details (if any) --}}
                    @if(isset($alert['details']) && count($alert['details']) > 0)
                        <div class="mb-4 space-y-1">
                            @foreach($alert['details'] as $detail)
                                <div class="text-sm
                                    @if($alert['type'] === 'critical') text-danger-700 dark:text-danger-300
                                    @elseif($alert['type'] === 'high') text-warning-700 dark:text-warning-300
                                    @elseif($alert['type'] === 'medium') text-info-700 dark:text-info-300
                                    @else text-gray-700 dark:text-gray-300
                                    @endif">
                                    @if(isset($detail['url']))
                                        <a href="{{ $detail['url'] }}" target="_blank" class="hover:underline flex items-center gap-1">
                                            → {{ $detail['text'] }}
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    @else
                                        → {{ $detail['text'] }}
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Actions --}}
                    @if(isset($alert['actions']) && count($alert['actions']) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($alert['actions'] as $action)
                                <a href="{{ $action['url'] ?? '#' }}"
                                   @if(isset($action['url']) && str_starts_with($action['url'], 'tel:'))
                                   @elseif(!isset($action['url']) || $action['url'] === '#')
                                       onclick="alert('Nutzen Sie bitte den entsprechenden Button im Seiten-Header'); return false;"
                                   @else
                                       target="_blank"
                                   @endif
                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md font-medium text-white shadow-sm hover:shadow transition-shadow duration-150
                                    @if($action['color'] === 'danger') bg-danger-600 hover:bg-danger-700
                                    @elseif($action['color'] === 'warning') bg-warning-600 hover:bg-warning-700
                                    @elseif($action['color'] === 'success') bg-success-600 hover:bg-success-700
                                    @elseif($action['color'] === 'info') bg-info-600 hover:bg-info-700
                                    @else bg-primary-600 hover:bg-primary-700
                                    @endif">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                    {{ $action['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@endif
