@php
    $call = $getRecord();
    $transcript = $call->transcript ?? $call->webhook_data['transcript'] ?? null;
    $mlPrediction = $call->mlPrediction ?? null;
    $sentenceSentiments = $mlPrediction ? ($mlPrediction->sentence_sentiments ?? []) : [];
    $overallSentiment = $mlPrediction ? $mlPrediction->sentiment_label : ($call->sentiment ?? 'neutral');
    $sentimentScore = $mlPrediction ? $mlPrediction->sentiment_score : 0;
    $confidence = $mlPrediction ? $mlPrediction->prediction_confidence : 0;
    $transcriptObject = $call->transcript_object ?? [];
@endphp

<div class="w-full" x-data="transcriptSentimentViewer(@js($getRecord()->id), @js($sentenceSentiments))">
    
    @if($transcript || count($transcriptObject) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            {{-- Header with Professional Sentiment Indicator --}}
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Gesprächsverlauf
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Vollständige Transkription mit Sentiment-Analyse
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        {{-- Professional Sentiment Indicator --}}
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Gesamtstimmung:</span>
                            <div class="flex items-center gap-1">
                                @if($overallSentiment === 'positive')
                                    <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-green-500"></span>
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400">Positiv</span>
                                @elseif($overallSentiment === 'negative')
                                    <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-red-500"></span>
                                    <span class="text-sm font-medium text-red-600 dark:text-red-400">Negativ</span>
                                @else
                                    <span class="inline-flex items-center justify-center w-3 h-3 rounded-full bg-gray-400"></span>
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Neutral</span>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                                    ({{ number_format($sentimentScore, 2) }})
                                </span>
                            </div>
                        </div>
                        
                        {{-- Confidence Badge --}}
                        @if($mlPrediction && $confidence > 0)
                            <div class="flex items-center gap-1 text-xs">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ round($confidence * 100) }}% Konfidenz
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Sentiment Distribution Bar --}}
                @if($mlPrediction)
                    <div class="mt-4">
                        <div class="flex h-2 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                            @php
                                $negPct = round(($mlPrediction->negative_probability ?? 0) * 100);
                                $neuPct = round(($mlPrediction->neutral_probability ?? 0) * 100);
                                $posPct = round(($mlPrediction->positive_probability ?? 0) * 100);
                            @endphp
                            @if($negPct > 0)
                                <div class="bg-red-500 h-full transition-all duration-500" 
                                     style="width: {{ $negPct }}%"
                                     title="Negativ: {{ $negPct }}%">
                                </div>
                            @endif
                            @if($neuPct > 0)
                                <div class="bg-gray-400 h-full transition-all duration-500" 
                                     style="width: {{ $neuPct }}%"
                                     title="Neutral: {{ $neuPct }}%">
                                </div>
                            @endif
                            @if($posPct > 0)
                                <div class="bg-green-500 h-full transition-all duration-500" 
                                     style="width: {{ $posPct }}%"
                                     title="Positiv: {{ $posPct }}%">
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Modern Chat-Style Transcript --}}
            <div class="max-h-[600px] overflow-y-auto">
                <div class="p-6 space-y-4">
                    @if(count($transcriptObject) > 0)
                        {{-- Structured transcript from transcript_object --}}
                        @foreach($transcriptObject as $index => $utterance)
                            @php
                                $isAgent = in_array($utterance['role'] ?? '', ['agent', 'ai', 'bot']);
                                $sentiment = null;
                                $sentimentClass = '';
                                
                                // Find matching sentiment for this utterance
                                if (count($sentenceSentiments) > $index) {
                                    $sentiment = $sentenceSentiments[$index]['sentiment'] ?? 'neutral';
                                    $sentimentClass = match($sentiment) {
                                        'positive' => 'border-green-500',
                                        'negative' => 'border-red-500',
                                        default => 'border-gray-300'
                                    };
                                }
                            @endphp
                            
                            <div class="flex {{ $isAgent ? 'justify-start' : 'justify-end' }} gap-3 group">
                                {{-- Avatar --}}
                                @if($isAgent)
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                @endif
                                
                                {{-- Message Bubble --}}
                                <div class="max-w-[70%]">
                                    <div class="rounded-lg px-4 py-3 {{ $isAgent ? 'bg-gray-100 dark:bg-gray-700' : 'bg-primary-500 text-white' }} {{ $sentimentClass }} border-l-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm {{ $isAgent ? 'text-gray-900 dark:text-gray-100' : 'text-white' }}">
                                                {{ $utterance['content'] ?? '' }}
                                            </p>
                                            @if($sentiment)
                                                <span class="flex-shrink-0 inline-flex items-center justify-center w-2 h-2 rounded-full
                                                    {{ $sentiment === 'positive' ? 'bg-green-500' : ($sentiment === 'negative' ? 'bg-red-500' : 'bg-gray-400') }}
                                                " title="{{ ucfirst($sentiment) }}"></span>
                                            @endif
                                        </div>
                                        
                                        {{-- Metadata --}}
                                        <div class="flex items-center gap-3 mt-1 text-xs {{ $isAgent ? 'text-gray-500 dark:text-gray-400' : 'text-primary-100' }}">
                                            <span>{{ $isAgent ? 'AI Agent' : 'Kunde' }}</span>
                                            @if(isset($utterance['start_time']))
                                                <span>{{ gmdate("i:s", $utterance['start_time']) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Customer Avatar --}}
                                @if(!$isAgent)
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @elseif(count($sentenceSentiments) > 0)
                        {{-- ML-based sentence display with professional design --}}
                        @foreach($sentenceSentiments as $sentence)
                            @php
                                $sentimentIndicator = match($sentence['sentiment'] ?? 'neutral') {
                                    'positive' => 'border-green-500',
                                    'negative' => 'border-red-500',
                                    default => 'border-gray-300'
                                };
                                $bgClass = match($sentence['sentiment'] ?? 'neutral') {
                                    'positive' => 'bg-green-50 dark:bg-green-900/10',
                                    'negative' => 'bg-red-50 dark:bg-red-900/10',
                                    default => 'bg-gray-50 dark:bg-gray-800'
                                };
                            @endphp
                            
                            <div class="group relative p-4 rounded-lg {{ $bgClass }} {{ $sentimentIndicator }} border-l-4 sentiment-sentence hover:shadow-md transition-all duration-200"
                                 data-sentiment="{{ $sentence['sentiment'] }}"
                                 data-score="{{ $sentence['score'] ?? 0 }}"
                                 @if(isset($sentence['start_time']))
                                 data-start-time="{{ $sentence['start_time'] }}"
                                 data-end-time="{{ $sentence['end_time'] ?? '' }}"
                                 @endif>
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $sentence['text'] }}
                                    </p>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="inline-flex items-center justify-center w-2 h-2 rounded-full
                                            {{ $sentence['sentiment'] === 'positive' ? 'bg-green-500' : ($sentence['sentiment'] === 'negative' ? 'bg-red-500' : 'bg-gray-400') }}
                                        "></span>
                                        @if(isset($sentence['score']))
                                            <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                {{ number_format($sentence['score'], 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if(isset($sentence['start_time']))
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ gmdate("i:s", $sentence['start_time']) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        {{-- Fallback to simple transcript parsing --}}
                        @php
                            $lines = explode("\n", $transcript);
                            foreach($lines as $line) {
                                $isAgent = str_starts_with($line, 'Agent:') || str_starts_with($line, 'AI:');
                                $isCustomer = str_starts_with($line, 'Customer:') || str_starts_with($line, 'Kunde:') || str_starts_with($line, 'Anrufer:');
                                
                                if ($isAgent || $isCustomer) {
                                    $content = trim(substr($line, strpos($line, ':') + 1));
                                    if (empty($content)) continue;
                                    
                                    echo '<div class="flex ' . ($isAgent ? 'justify-start' : 'justify-end') . ' gap-3 mb-4">';
                                    
                                    if ($isAgent) {
                                        echo '<div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                                    </svg>
                                                </div>
                                            </div>';
                                    }
                                    
                                    echo '<div class="max-w-[70%]">
                                            <div class="rounded-lg px-4 py-3 ' . ($isAgent ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' : 'bg-primary-500 text-white') . '">
                                                <p class="text-sm">' . htmlspecialchars($content) . '</p>
                                                <div class="mt-1 text-xs ' . ($isAgent ? 'text-gray-500 dark:text-gray-400' : 'text-primary-100') . '">
                                                    ' . ($isAgent ? 'AI Agent' : 'Kunde') . '
                                                </div>
                                            </div>
                                          </div>';
                                    
                                    if (!$isAgent) {
                                        echo '<div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                </div>
                                            </div>';
                                    }
                                    
                                    echo '</div>';
                                } elseif (trim($line)) {
                                    echo '<div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded text-sm text-gray-700 dark:text-gray-300">' 
                                         . htmlspecialchars($line) . '</div>';
                                }
                            }
                        @endphp
                    @endif
                </div>
            </div>
            
            {{-- Statistics Footer --}}
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ str_word_count($transcript) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Wörter</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ count($sentenceSentiments) ?: substr_count($transcript, "\n") + 1 }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Sätze</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ count(array_filter($sentenceSentiments, fn($s) => ($s['sentiment'] ?? '') === 'positive')) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Positiv</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $call->duration_sec ? round(str_word_count($transcript) / max(($call->duration_sec / 60), 1)) : 0 }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Wörter/Min</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Kein Transkript verfügbar
            </p>
        </div>
    @endif
</div>

@push('scripts')
<script>
function transcriptSentimentViewer(callId, sentenceSentiments) {
    return {
        callId: callId,
        sentenceSentiments: sentenceSentiments || [],
        selectedSentence: null,
        highlightedSentence: null,
        
        init() {
            // Add click handler to sentences
            this.$el.addEventListener('click', (e) => {
                const sentence = e.target.closest('.sentiment-sentence');
                if (sentence) {
                    this.selectSentence(sentence);
                }
            });
            
            // Listen for audio time updates
            window.addEventListener('audio-time-update', (event) => {
                this.highlightPlayingSentence(event.detail);
            });
        },
        
        selectSentence(element) {
            // Remove previous selection
            if (this.selectedSentence) {
                this.selectedSentence.classList.remove('ring-2', 'ring-primary-500', 'shadow-lg');
            }
            
            // Add selection to clicked sentence
            this.selectedSentence = element;
            element.classList.add('ring-2', 'ring-primary-500', 'shadow-lg');
            
            // Emit event for audio player sync
            window.dispatchEvent(new CustomEvent('sentence-selected', {
                detail: {
                    startTime: element.dataset.startTime,
                    endTime: element.dataset.endTime
                }
            }));
        },
        
        highlightPlayingSentence(detail) {
            // Remove previous highlight
            if (this.highlightedSentence) {
                this.highlightedSentence.classList.remove('bg-yellow-50', 'dark:bg-yellow-900/10', 'border-yellow-500');
            }
            
            // Find and highlight current sentence
            if (detail.sentenceIndex >= 0) {
                const sentences = this.$el.querySelectorAll('.sentiment-sentence');
                if (sentences[detail.sentenceIndex]) {
                    this.highlightedSentence = sentences[detail.sentenceIndex];
                    this.highlightedSentence.classList.add('bg-yellow-50', 'dark:bg-yellow-900/10', 'border-yellow-500');
                    
                    // Scroll into view if needed
                    this.highlightedSentence.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }
            }
        }
    }
}
</script>
@endpush

<style>
.sentiment-sentence {
    transition: all 0.2s ease;
    cursor: pointer;
}

.sentiment-sentence:hover {
    transform: translateX(2px);
}

/* Professional play indicator */
.sentiment-sentence.bg-yellow-50::before {
    content: '▸';
    position: absolute;
    left: -18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgb(251 191 36);
    font-size: 14px;
    font-weight: bold;
}

/* Smooth scrollbar for transcript container */
.max-h-\[600px\]::-webkit-scrollbar {
    width: 8px;
}

.max-h-\[600px\]::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
}

.max-h-\[600px\]::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.max-h-\[600px\]::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

.dark .max-h-\[600px\]::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.dark .max-h-\[600px\]::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

.dark .max-h-\[600px\]::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>