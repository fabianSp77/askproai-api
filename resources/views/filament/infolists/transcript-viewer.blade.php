<div class="w-full">
    @php
        $transcript = $getRecord()->transcript ?? $getRecord()->webhook_data['transcript'] ?? null;
        $analysis = $getRecord()->analysis ?? [];
        $highlights = $analysis['important_phrases'] ?? [];
        $entities = $analysis['entities'] ?? [];
    @endphp
    
    @if($transcript)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 space-y-4">
            {{-- Wichtige Phrasen --}}
            @if(!empty($highlights))
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Wichtige Phrasen:</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($highlights as $phrase)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                {{ $phrase }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
            
            {{-- Transkript mit Highlighting --}}
            <div class="prose prose-sm dark:prose-invert max-w-none">
                @php
                    $highlightedTranscript = $transcript;
                    
                    // Highlight entities
                    foreach($entities as $type => $value) {
                        $color = match($type) {
                            'date', 'time' => 'blue',
                            'phone', 'email' => 'green',
                            'name' => 'purple',
                            'service' => 'orange',
                            default => 'gray'
                        };
                        
                        $highlightedTranscript = preg_replace(
                            '/\b' . preg_quote($value, '/') . '\b/i',
                            '<mark class="bg-' . $color . '-100 text-' . $color . '-800 dark:bg-' . $color . '-900 dark:text-' . $color . '-200 px-1 rounded">' . $value . '</mark>',
                            $highlightedTranscript
                        );
                    }
                    
                    // Format dialogue
                    $lines = explode("\n", $highlightedTranscript);
                    $formattedLines = [];
                    
                    foreach($lines as $line) {
                        if (str_starts_with($line, 'Agent:') || str_starts_with($line, 'AI:')) {
                            $formattedLines[] = '<div class="mb-2"><strong class="text-primary-600 dark:text-primary-400">AI Agent:</strong> ' . substr($line, strpos($line, ':') + 1) . '</div>';
                        } elseif (str_starts_with($line, 'Customer:') || str_starts_with($line, 'Kunde:') || str_starts_with($line, 'Anrufer:')) {
                            $formattedLines[] = '<div class="mb-2"><strong class="text-gray-600 dark:text-gray-400">Kunde:</strong> ' . substr($line, strpos($line, ':') + 1) . '</div>';
                        } else {
                            $formattedLines[] = '<div class="mb-2">' . $line . '</div>';
                        }
                    }
                    
                    echo implode('', $formattedLines);
                @endphp
            </div>
            
            {{-- Konversations-Statistiken --}}
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ str_word_count($transcript) }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">Wörter</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ substr_count($transcript, "\n") + 1 }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">Gesprächswechsel</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ round(str_word_count($transcript) / max(($getRecord()->duration_sec / 60), 1)) }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">Wörter/Min</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-gray-500 dark:text-gray-400 text-sm italic">
            Kein Transkript verfügbar
        </div>
    @endif
</div>