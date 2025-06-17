<div class="space-y-4">
    @if($hasToolCalls && is_array($transcriptObject))
        <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            <span class="inline-flex items-center gap-1">
                <x-heroicon-m-wrench-screwdriver class="w-4 h-4" />
                Detaillierte Konversation mit Tool-Aufrufen
            </span>
        </div>
        
        @foreach($transcriptObject as $entry)
            @php
                $role = $entry['role'] ?? 'unknown';
                $content = $entry['content'] ?? '';
                $words = $entry['words'] ?? [];
                $metadata = $entry['metadata'] ?? [];
            @endphp
            
            @if($role === 'agent')
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                        <span class="text-lg">🤖</span>
                    </div>
                    <div class="flex-1">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">KI-Agent</span>
                                @if(isset($metadata['response_id']))
                                    <span class="text-xs text-gray-500">#{{ $metadata['response_id'] }}</span>
                                @endif
                            </div>
                            <p class="text-sm">{{ $content }}</p>
                            
                            @if(!empty($words))
                                <details class="mt-2">
                                    <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                                        Zeitstempel anzeigen ({{ count($words) }} Wörter)
                                    </summary>
                                    <div class="mt-2 text-xs text-gray-600 space-y-1 max-h-32 overflow-y-auto">
                                        @foreach($words as $word)
                                            <div class="flex justify-between">
                                                <span>{{ $word['word'] ?? '' }}</span>
                                                <span class="text-gray-400">{{ round($word['start'] ?? 0, 2) }}s - {{ round($word['end'] ?? 0, 2) }}s</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
                
            @elseif($role === 'user')
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <span class="text-lg">👤</span>
                    </div>
                    <div class="flex-1">
                        <div class="bg-gray-50 dark:bg-gray-900/30 rounded-lg p-4">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400 block mb-2">Kunde</span>
                            <p class="text-sm">{{ $content }}</p>
                            
                            @if(!empty($words))
                                <details class="mt-2">
                                    <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                                        Zeitstempel anzeigen ({{ count($words) }} Wörter)
                                    </summary>
                                    <div class="mt-2 text-xs text-gray-600 space-y-1 max-h-32 overflow-y-auto">
                                        @foreach($words as $word)
                                            <div class="flex justify-between">
                                                <span>{{ $word['word'] ?? '' }}</span>
                                                <span class="text-gray-400">{{ round($word['start'] ?? 0, 2) }}s - {{ round($word['end'] ?? 0, 2) }}s</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
                
            @elseif($role === 'tool_call_invocation')
                <div class="flex gap-3 ml-12">
                    <div class="flex-1">
                        <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 p-3">
                            <div class="flex items-center gap-2 mb-1">
                                <x-heroicon-m-wrench-screwdriver class="w-4 h-4 text-amber-600" />
                                <span class="text-xs font-medium text-amber-700 dark:text-amber-300">
                                    Tool-Aufruf: {{ $entry['name'] ?? 'Unbekannt' }}
                                </span>
                            </div>
                            @if(isset($entry['arguments']))
                                <details>
                                    <summary class="text-xs text-gray-600 cursor-pointer">Parameter anzeigen</summary>
                                    <pre class="mt-2 text-xs bg-white dark:bg-gray-800 p-2 rounded overflow-x-auto">{{ json_encode(json_decode($entry['arguments'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
                
            @elseif($role === 'tool_call_result')
                <div class="flex gap-3 ml-12">
                    <div class="flex-1">
                        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-3">
                            <div class="flex items-center gap-2 mb-1">
                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-600" />
                                <span class="text-xs font-medium text-green-700 dark:text-green-300">Tool-Ergebnis</span>
                            </div>
                            <pre class="text-xs bg-white dark:bg-gray-800 p-2 rounded overflow-x-auto">{{ $content }}</pre>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
        
        @if(empty($transcriptObject))
            <div class="text-center text-gray-500 py-8">
                <x-heroicon-o-chat-bubble-left-right class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Kein detailliertes Transkript verfügbar</p>
            </div>
        @endif
    @else
        <div class="text-center text-gray-500 py-8">
            <x-heroicon-o-chat-bubble-left-right class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>Kein strukturiertes Transkript verfügbar</p>
        </div>
    @endif
</div>