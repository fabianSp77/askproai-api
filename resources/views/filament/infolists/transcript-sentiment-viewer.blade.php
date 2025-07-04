<div class="w-full" x-data="transcriptSentimentViewer(@js($getRecord()->id))">
    @php
        $call = $getRecord();
        $transcript = $call->transcript ?? $call->webhook_data['transcript'] ?? null;
        $mlPrediction = $call->mlPrediction ?? null;
        $sentenceSentiments = $mlPrediction ? ($mlPrediction->sentence_sentiments ?? []) : [];
        $overallSentiment = $mlPrediction ? $mlPrediction->sentiment_label : ($call->sentiment ?? 'neutral');
        $sentimentScore = $mlPrediction ? $mlPrediction->sentiment_score : 0;
        $confidence = $mlPrediction ? $mlPrediction->prediction_confidence : 0;
    @endphp
    
    @if($transcript)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg space-y-4">
            {{-- Overall Sentiment Summary --}}
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Gesprächsverlauf & Analyse
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Automatische Analyse der Gesprächsstimmung
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">
                                @if($overallSentiment === 'positive')
                                    😊
                                @elseif($overallSentiment === 'negative')
                                    😔
                                @else
                                    😐
                                @endif
                            </span>
                            <div>
                                <span class="text-lg font-bold 
                                    @if($overallSentiment === 'positive') text-green-600 dark:text-green-400
                                    @elseif($overallSentiment === 'negative') text-red-600 dark:text-red-400
                                    @else text-gray-600 dark:text-gray-400
                                    @endif">
                                    {{ ucfirst($overallSentiment) }}
                                </span>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Score: {{ number_format($sentimentScore, 2) }}
                                </div>
                            </div>
                        </div>
                        @if($mlPrediction)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Konfidenz: {{ round($confidence * 100) }}%
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Sentiment Distribution Bar --}}
                @if($mlPrediction)
                    <div class="mt-4">
                        <div class="flex h-4 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700">
                            @php
                                $posPct = round(($mlPrediction->positive_probability ?? 0) * 100);
                                $neuPct = round(($mlPrediction->neutral_probability ?? 0) * 100);
                                $negPct = round(($mlPrediction->negative_probability ?? 0) * 100);
                            @endphp
                            @if($negPct > 0)
                                <div class="bg-red-500 dark:bg-red-600 h-full transition-all duration-500" 
                                     style="width: {{ $negPct }}%"
                                     title="Negativ: {{ $negPct }}%">
                                </div>
                            @endif
                            @if($neuPct > 0)
                                <div class="bg-gray-400 dark:bg-gray-500 h-full transition-all duration-500" 
                                     style="width: {{ $neuPct }}%"
                                     title="Neutral: {{ $neuPct }}%">
                                </div>
                            @endif
                            @if($posPct > 0)
                                <div class="bg-green-500 dark:bg-green-600 h-full transition-all duration-500" 
                                     style="width: {{ $posPct }}%"
                                     title="Positiv: {{ $posPct }}%">
                                </div>
                            @endif
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>Negativ ({{ $negPct }}%)</span>
                            <span>Neutral ({{ $neuPct }}%)</span>
                            <span>Positiv ({{ $posPct }}%)</span>
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Transcript with Sentiment Highlighting --}}
            <div class="p-6">
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    @if(count($sentenceSentiments) > 0)
                        {{-- ML-based sentence highlighting --}}
                        @foreach($sentenceSentiments as $sentence)
                            @php
                                $sentimentClass = match($sentence['sentiment'] ?? 'neutral') {
                                    'positive' => 'bg-green-100 dark:bg-green-900/30 text-green-900 dark:text-green-100',
                                    'negative' => 'bg-red-100 dark:bg-red-900/30 text-red-900 dark:text-red-100',
                                    default => 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100'
                                };
                                $borderClass = match($sentence['sentiment'] ?? 'neutral') {
                                    'positive' => 'border-l-4 border-green-500',
                                    'negative' => 'border-l-4 border-red-500',
                                    default => 'border-l-4 border-gray-400'
                                };
                            @endphp
                            <div class="mb-2 p-3 rounded-lg {{ $sentimentClass }} {{ $borderClass }} sentiment-sentence"
                                 data-sentiment="{{ $sentence['sentiment'] }}"
                                 data-score="{{ $sentence['score'] ?? 0 }}"
                                 @if(isset($sentence['start_time']))
                                 data-start-time="{{ $sentence['start_time'] }}"
                                 data-end-time="{{ $sentence['end_time'] ?? '' }}"
                                 @endif>
                                <span class="text-sm">{{ $sentence['text'] }}</span>
                                @if(isset($sentence['score']))
                                    <span class="ml-2 text-xs opacity-70">
                                        ({{ number_format($sentence['score'], 2) }})
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    @else
                        {{-- Fallback to simple transcript display --}}
                        @php
                            $lines = explode("\n", $transcript);
                            $formattedLines = [];
                            
                            foreach($lines as $line) {
                                if (str_starts_with($line, 'Agent:') || str_starts_with($line, 'AI:')) {
                                    $formattedLines[] = '<div class="mb-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded">
                                        <strong class="text-primary-600 dark:text-primary-400">AI Agent:</strong> ' 
                                        . substr($line, strpos($line, ':') + 1) . '</div>';
                                } elseif (str_starts_with($line, 'Customer:') || str_starts_with($line, 'Kunde:') || str_starts_with($line, 'Anrufer:')) {
                                    $formattedLines[] = '<div class="mb-2 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                        <strong class="text-gray-600 dark:text-gray-400">Kunde:</strong> ' 
                                        . substr($line, strpos($line, ':') + 1) . '</div>';
                                } elseif (trim($line)) {
                                    $formattedLines[] = '<div class="mb-2 p-2">' . $line . '</div>';
                                }
                            }
                            
                            echo implode('', $formattedLines);
                        @endphp
                    @endif
                </div>
                
                {{-- Key Phrases and Insights --}}
                @if($mlPrediction && $mlPrediction->feature_contributions)
                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                            Wichtige Faktoren
                        </h4>
                        <div class="space-y-1">
                            @foreach(array_slice($mlPrediction->top_features, 0, 5) as $feature => $value)
                                <div class="flex justify-between text-xs">
                                    <span class="text-blue-700 dark:text-blue-200">
                                        {{ str_replace('_', ' ', ucfirst($feature)) }}
                                    </span>
                                    <span class="font-mono text-blue-600 dark:text-blue-300">
                                        {{ number_format($value, 2) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Conversation Statistics --}}
            <div class="p-6 pt-0">
                <div class="grid grid-cols-4 gap-4 text-sm">
                    <div class="text-center p-3 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ str_word_count($transcript) }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">Wörter</div>
                    </div>
                    <div class="text-center p-3 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ count($sentenceSentiments) ?: substr_count($transcript, "\n") + 1 }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">Sätze</div>
                    </div>
                    <div class="text-center p-3 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                        <div class="text-2xl font-bold 
                            @if(count(array_filter($sentenceSentiments, fn($s) => ($s['sentiment'] ?? '') === 'positive')) > count($sentenceSentiments) / 2)
                                text-green-600 dark:text-green-400
                            @elseif(count(array_filter($sentenceSentiments, fn($s) => ($s['sentiment'] ?? '') === 'negative')) > count($sentenceSentiments) / 2)
                                text-red-600 dark:text-red-400
                            @else
                                text-gray-600 dark:text-gray-400
                            @endif">
                            {{ count(array_filter($sentenceSentiments, fn($s) => ($s['sentiment'] ?? '') === 'positive')) }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">Positive</div>
                    </div>
                    <div class="text-center p-3 bg-white dark:bg-gray-900 rounded-lg shadow-sm">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ round(str_word_count($transcript) / max(($call->duration_sec / 60), 1)) }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">Wörter/Min</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-gray-500 dark:text-gray-400 text-sm italic p-6 text-center">
            Kein Transkript verfügbar
        </div>
    @endif
</div>

@push('scripts')
<script>
function transcriptSentimentViewer(callId) {
    return {
        callId: callId,
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
                this.selectedSentence.classList.remove('ring-2', 'ring-primary-500');
            }
            
            // Add selection to clicked sentence
            this.selectedSentence = element;
            element.classList.add('ring-2', 'ring-primary-500');
            
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
                this.highlightedSentence.classList.remove('bg-yellow-100', 'dark:bg-yellow-900/30', 'border-yellow-500');
            }
            
            // Find and highlight current sentence
            if (detail.sentenceIndex >= 0) {
                const sentences = this.$el.querySelectorAll('.sentiment-sentence');
                if (sentences[detail.sentenceIndex]) {
                    this.highlightedSentence = sentences[detail.sentenceIndex];
                    this.highlightedSentence.classList.add('bg-yellow-100', 'dark:bg-yellow-900/30', 'border-yellow-500');
                    
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
    position: relative;
}

.sentiment-sentence:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.sentiment-sentence.ring-2 {
    animation: pulse 2s infinite;
}

/* Highlight for currently playing sentence */
.sentiment-sentence.bg-yellow-100 {
    background-color: rgb(254 249 195) !important;
    border-left-color: rgb(234 179 8) !important;
}

.dark .sentiment-sentence.bg-yellow-100 {
    background-color: rgba(234, 179, 8, 0.2) !important;
    border-left-color: rgb(234 179 8) !important;
}

/* Add a playing indicator */
.sentiment-sentence.bg-yellow-100::before {
    content: '▶';
    position: absolute;
    left: -20px;
    top: 50%;
    transform: translateY(-50%);
    color: rgb(234 179 8);
    font-size: 12px;
    animation: blink 1s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.3; }
}
</style>