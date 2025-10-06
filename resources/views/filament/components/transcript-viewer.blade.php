@php
    $transcript = $getRecord()->transcript;
    $callId = $getRecord()->id;
@endphp

@if($transcript)
<div class="transcript-viewer-container bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
    {{-- Header mit Suchfeld --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <span class="text-lg">📝</span>
            <h4 class="font-medium text-gray-900 dark:text-gray-100">Gesprächstranskript</h4>
        </div>

        {{-- Such-Funktion --}}
        <div class="relative">
            <input type="text"
                   id="transcript-search-{{ $callId }}"
                   placeholder="Im Transkript suchen..."
                   class="pl-10 pr-4 py-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    {{-- Transkript Content --}}
    <div class="transcript-content bg-white dark:bg-gray-700 rounded-lg p-6 max-h-96 overflow-y-auto">
        @php
            // Parse transcript if it's an array
            if (is_array($transcript)) {
                if (isset($transcript['text'])) {
                    $transcriptText = $transcript['text'];
                } elseif (isset($transcript['transcript'])) {
                    $transcriptText = $transcript['transcript'];
                } else {
                    $transcriptText = json_encode($transcript, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            } else {
                $transcriptText = $transcript;
            }

            // Try to detect speaker patterns and format accordingly
            $lines = explode("\n", $transcriptText);
            $formattedTranscript = [];
            $currentSpeaker = null;
            $speakerColors = [
                'agent' => 'text-blue-600 dark:text-blue-400',
                'kunde' => 'text-green-600 dark:text-green-400',
                'system' => 'text-gray-600 dark:text-gray-400'
            ];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Detect speaker patterns
                $speaker = null;
                $content = $line;

                // Common patterns: "Agent:", "Kunde:", "System:", or [Agent], [Kunde]
                if (preg_match('/^(Agent|Kunde|System|Customer|User|Assistant|Bot|AI)[\:\[\]]/i', $line, $matches)) {
                    $speaker = strtolower($matches[1]);
                    // Map English to German
                    $speakerMap = [
                        'customer' => 'kunde',
                        'user' => 'kunde',
                        'assistant' => 'agent',
                        'bot' => 'agent',
                        'ai' => 'agent'
                    ];
                    $speaker = $speakerMap[$speaker] ?? $speaker;
                    $content = trim(preg_replace('/^[^\:\[\]]+[\:\[\]]/', '', $line));
                }

                // Timestamp pattern detection [00:00:00] or (00:00)
                $timestamp = '';
                if (preg_match('/^\[?(\d{1,2}:\d{2}(?::\d{2})?)\]?\s*/', $content, $timeMatch)) {
                    $timestamp = $timeMatch[1];
                    $content = trim(str_replace($timeMatch[0], '', $content));
                }

                if ($speaker !== $currentSpeaker && $speaker !== null) {
                    $currentSpeaker = $speaker;
                }

                $formattedTranscript[] = [
                    'speaker' => $currentSpeaker,
                    'timestamp' => $timestamp,
                    'content' => $content
                ];
            }
        @endphp

        <div class="space-y-4" id="transcript-text-{{ $callId }}">
            @if(count($formattedTranscript) > 0 && $formattedTranscript[0]['speaker'] !== null)
                {{-- Formatted transcript with speakers --}}
                @php $lastSpeaker = null; @endphp
                @foreach($formattedTranscript as $entry)
                    @if($entry['speaker'] !== $lastSpeaker)
                        @if($lastSpeaker !== null)
                            </div></div>
                        @endif
                        <div class="transcript-block">
                            <div class="flex items-start gap-3">
                                {{-- Speaker Avatar --}}
                                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                    @if($entry['speaker'] === 'agent') bg-blue-100 dark:bg-blue-900
                                    @elseif($entry['speaker'] === 'kunde') bg-green-100 dark:bg-green-900
                                    @else bg-gray-100 dark:bg-gray-800
                                    @endif">
                                    @if($entry['speaker'] === 'agent')
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"></path>
                                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"></path>
                                        </svg>
                                    @elseif($entry['speaker'] === 'kunde')
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </div>

                                <div class="flex-1">
                                    {{-- Speaker Name --}}
                                    <div class="font-medium text-sm mb-1
                                        @if($entry['speaker'] === 'agent') text-blue-600 dark:text-blue-400
                                        @elseif($entry['speaker'] === 'kunde') text-green-600 dark:text-green-400
                                        @else text-gray-600 dark:text-gray-400
                                        @endif">
                                        {{ ucfirst($entry['speaker']) }}
                                    </div>
                                    <div class="space-y-1">
                        @php $lastSpeaker = $entry['speaker']; @endphp
                    @endif

                    <div class="transcript-line">
                        @if($entry['timestamp'])
                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-2">[{{ $entry['timestamp'] }}]</span>
                        @endif
                        <span class="text-gray-900 dark:text-gray-100">{{ $entry['content'] }}</span>
                    </div>
                @endforeach
                @if($lastSpeaker !== null)
                    </div></div></div>
                @endif
            @else
                {{-- Fallback to plain text if no speaker detection --}}
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <div class="whitespace-pre-wrap text-gray-900 dark:text-gray-100">{{ $transcriptText }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Footer with statistics --}}
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-4">
                <span>📊 Wörter: <strong id="word-count-{{ $callId }}">{{ str_word_count($transcriptText) }}</strong></span>
                <span>⏱️ Geschätzte Lesezeit: <strong>{{ ceil(str_word_count($transcriptText) / 200) }} Min.</strong></span>
            </div>
            <button type="button"
                    onclick="copyTranscript('{{ $callId }}')"
                    class="inline-flex items-center gap-1 px-3 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                </svg>
                Kopieren
            </button>
        </div>
    </div>
</div>

{{-- Search and Copy JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const callId = '{{ $callId }}';
    const searchInput = document.getElementById(`transcript-search-${callId}`);
    const transcriptText = document.getElementById(`transcript-text-${callId}`);

    if (searchInput && transcriptText) {
        let originalHTML = transcriptText.innerHTML;

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();

            if (searchTerm === '') {
                transcriptText.innerHTML = originalHTML;
                return;
            }

            // Clear previous highlights
            transcriptText.innerHTML = originalHTML;

            // Highlight search terms
            const walker = document.createTreeWalker(
                transcriptText,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );

            const textNodes = [];
            while (walker.nextNode()) {
                textNodes.push(walker.currentNode);
            }

            textNodes.forEach(node => {
                const text = node.textContent;
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                if (regex.test(text)) {
                    const span = document.createElement('span');
                    span.innerHTML = text.replace(regex, '<mark class="bg-yellow-300 dark:bg-yellow-600 text-gray-900 dark:text-gray-100 px-0.5 rounded">$1</mark>');
                    node.parentNode.replaceChild(span, node);
                }
            });
        });
    }
});

function copyTranscript(callId) {
    const transcriptEl = document.getElementById(`transcript-text-${callId}`);
    if (transcriptEl) {
        const text = transcriptEl.innerText || transcriptEl.textContent;
        navigator.clipboard.writeText(text).then(() => {
            // Show success message
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> Kopiert!';
            setTimeout(() => {
                btn.innerHTML = originalText;
            }, 2000);
        });
    }
}
</script>

<style>
/* Smooth scroll for transcript */
.transcript-content {
    scroll-behavior: smooth;
}

/* Custom scrollbar for transcript */
.transcript-content::-webkit-scrollbar {
    width: 8px;
}

.transcript-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.transcript-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.transcript-content::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Dark mode scrollbar */
.dark .transcript-content::-webkit-scrollbar-track {
    background: #374151;
}

.dark .transcript-content::-webkit-scrollbar-thumb {
    background: #6b7280;
}

.dark .transcript-content::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>

@else
<div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center text-gray-500 dark:text-gray-400">
    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    <p>Kein Transkript verfügbar</p>
</div>
@endif