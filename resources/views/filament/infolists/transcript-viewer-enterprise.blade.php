@php
    use App\Helpers\AutoTranslateHelper;
    
    $call = $getRecord();
    $transcript = $call->transcript ?? $call->webhook_data['transcript'] ?? null;
    $mlPrediction = $call->mlPrediction ?? null;
    $sentenceSentiments = $mlPrediction ? ($mlPrediction->sentence_sentiments ?? []) : [];
    $transcriptObject = $call->transcript_object ?? [];
    
    // Get translation data - for now we'll handle it directly
    $showTranslateToggle = auth()->user()?->auto_translate_content ?? false;
    $transcriptData = null;
    if ($showTranslateToggle && $transcript) {
        $transcriptData = AutoTranslateHelper::getToggleableContent(
            $transcript,
            $call->detected_language
        );
    }
@endphp

<div class="w-full" x-data="transcriptViewerEnterprise(@js($getRecord()->id), @js($sentenceSentiments), @js($showTranslateToggle), @js($transcriptData))">
    
    @if($transcript || count($transcriptObject) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Transcript Header --}}
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Gesprächsverlauf</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ str_word_count($transcript) }} Wörter • 
                                {{ count($sentenceSentiments) ?: count($transcriptObject) ?: substr_count($transcript, "\n") + 1 }} Beiträge
                            </p>
                        </div>
                    </div>
                    
                    {{-- View Options --}}
                    <div class="flex items-center gap-2">
                        @if($showTranslateToggle && $transcriptData && $transcriptData['should_translate'])
                            <div class="flex items-center gap-1 mr-2">
                                <button 
                                    @click="showTranslated = !showTranslated"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <svg 
                                        class="w-3 h-3" 
                                        fill="none" 
                                        stroke="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path 
                                            stroke-linecap="round" 
                                            stroke-linejoin="round" 
                                            stroke-width="2" 
                                            d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"
                                        ></path>
                                    </svg>
                                    <span x-text="showTranslated ? 'Original anzeigen' : 'Übersetzung anzeigen'"></span>
                                </button>
                                
                                @if($transcriptData['source_language'] && $transcriptData['target_language'])
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-show="!showTranslated">{{ strtoupper($transcriptData['source_language']) }}</span>
                                        <span x-show="showTranslated">{{ strtoupper($transcriptData['target_language']) }}</span>
                                    </span>
                                @endif
                            </div>
                        @endif
                        
                        <button 
                            @click="viewMode = 'conversation'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                            :class="viewMode === 'conversation' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            Gespräch
                        </button>
                        <button 
                            @click="viewMode = 'sentiment'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
                            :class="viewMode === 'sentiment' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            x-show="sentenceSentiments.length > 0"
                        >
                            Sentiment
                        </button>
                    </div>
                </div>
            </div>
            
            {{-- Transcript Content --}}
            <div class="max-h-[500px] overflow-y-auto custom-scrollbar">
                {{-- Conversation View --}}
                <div x-show="viewMode === 'conversation'" class="p-6 space-y-4">
                    @if(count($transcriptObject) > 0)
                        @foreach($transcriptObject as $index => $utterance)
                            @php
                                $isAgent = in_array($utterance['role'] ?? '', ['agent', 'ai', 'bot']);
                                $sentiment = count($sentenceSentiments) > $index ? $sentenceSentiments[$index]['sentiment'] ?? 'neutral' : 'neutral';
                            @endphp
                            
                            <div class="group hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-lg p-3 -m-3 transition-colors">
                                <div class="flex gap-3 {{ $isAgent ? '' : 'flex-row-reverse' }}">
                                    {{-- Avatar --}}
                                    <div class="flex-shrink-0">
                                        @if($isAgent)
                                            <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    {{-- Message Content --}}
                                    <div class="flex-1 max-w-[80%]">
                                        <div class="flex items-baseline gap-2 mb-1">
                                            <span class="text-xs font-medium {{ $isAgent ? 'text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $isAgent ? 'AI Agent' : 'Kunde' }}
                                            </span>
                                            @if(isset($utterance['start_time']))
                                                <span class="text-xs text-gray-400">
                                                    {{ gmdate("i:s", $utterance['start_time']) }}
                                                </span>
                                            @endif
                                            @if($sentiment !== 'neutral')
                                                <span class="inline-flex items-center justify-center w-2 h-2 rounded-full {{ $sentiment === 'positive' ? 'bg-green-500' : 'bg-red-500' }}" 
                                                      title="{{ ucfirst($sentiment) }}"></span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
                                            {{ $utterance['content'] ?? '' }}
                                        </div>
                                    </div>
                                </div>
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
                                    
                                    echo '<div class="group hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-lg p-3 -m-3 transition-colors">';
                                    echo '<div class="flex gap-3 ' . ($isAgent ? '' : 'flex-row-reverse') . '">';
                                    
                                    // Avatar
                                    echo '<div class="flex-shrink-0">';
                                    if ($isAgent) {
                                        echo '<div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>';
                                    } else {
                                        echo '<div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                            </div>';
                                    }
                                    echo '</div>';
                                    
                                    // Message
                                    echo '<div class="flex-1 max-w-[80%]">
                                            <div class="text-xs font-medium mb-1 ' . ($isAgent ? 'text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300') . '">'
                                            . ($isAgent ? 'AI Agent' : 'Kunde') . 
                                            '</div>
                                            <div class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">' 
                                            . htmlspecialchars($content) . 
                                            '</div>
                                          </div>';
                                    
                                    echo '</div></div>';
                                }
                            }
                        @endphp
                    @endif
                </div>
                
                {{-- Sentiment View --}}
                <div x-show="viewMode === 'sentiment'" class="p-6 space-y-3" style="display: none;">
                    @foreach($sentenceSentiments as $sentence)
                        @php
                            $bgClass = match($sentence['sentiment'] ?? 'neutral') {
                                'positive' => 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800',
                                'negative' => 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800',
                                default => 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700'
                            };
                            
                            $dotColor = match($sentence['sentiment'] ?? 'neutral') {
                                'positive' => 'bg-green-500',
                                'negative' => 'bg-red-500',
                                default => 'bg-gray-400'
                            };
                        @endphp
                        
                        <div class="p-4 rounded-lg border {{ $bgClass }} transition-all hover:shadow-sm sentiment-sentence cursor-pointer"
                             data-sentiment="{{ $sentence['sentiment'] }}"
                             data-score="{{ $sentence['score'] ?? 0 }}"
                             @if(isset($sentence['start_time']))
                             data-start-time="{{ $sentence['start_time'] }}"
                             data-end-time="{{ $sentence['end_time'] ?? '' }}"
                             @endif>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 mt-1.5 w-2 h-2 rounded-full {{ $dotColor }}"></span>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-gray-100 leading-relaxed">
                                        {{ $sentence['text'] }}
                                    </p>
                                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        @if(isset($sentence['start_time']))
                                            <span>{{ gmdate("i:s", $sentence['start_time']) }}</span>
                                        @endif
                                        @if(isset($sentence['score']))
                                            <span>Score: {{ number_format($sentence['score'], 2) }}</span>
                                        @endif
                                        <span>{{ ucfirst($sentence['sentiment'] ?? 'neutral') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Transcript Footer with Stats --}}
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-4">
                        @if(count($sentenceSentiments) > 0)
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                {{ count(array_filter($sentenceSentiments, fn($s) => ($s['sentiment'] ?? '') === 'positive')) }} Positiv
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                {{ count(array_filter($sentenceSentiments, fn($s) => ($s['sentiment'] ?? '') === 'neutral')) }} Neutral
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                {{ count(array_filter($sentenceSentiments, fn($s) => ($s['sentiment'] ?? '') === 'negative')) }} Negativ
                            </span>
                        @endif
                    </div>
                    <div>
                        {{ $call->duration_sec ? round(str_word_count($transcript) / max(($call->duration_sec / 60), 1)) : 0 }} Wörter/Min
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kein Transkript verfügbar
            </p>
        </div>
    @endif
</div>

@push('scripts')
<script>
function transcriptViewerEnterprise(callId, sentenceSentiments, showTranslateToggle, transcriptData) {
    return {
        callId: callId,
        sentenceSentiments: sentenceSentiments || [],
        viewMode: 'conversation',
        selectedSentence: null,
        showTranslated: false,
        showTranslateToggle: showTranslateToggle || false,
        transcriptData: transcriptData || null,
        transcriptObject: @js($transcriptObject),
        
        init() {
            // Add click handler to sentiment sentences
            this.$el.addEventListener('click', (e) => {
                const sentence = e.target.closest('.sentiment-sentence');
                if (sentence && this.viewMode === 'sentiment') {
                    this.selectSentence(sentence);
                }
            });
            
            // Listen for sentiment changes from audio player
            window.addEventListener('current-sentiment', (event) => {
                this.highlightCurrentSentiment(event.detail);
            });
        },
        
        selectSentence(element) {
            // Remove previous selection
            if (this.selectedSentence) {
                this.selectedSentence.classList.remove('ring-2', 'ring-primary-500', 'shadow-md');
            }
            
            // Add selection to clicked sentence
            this.selectedSentence = element;
            element.classList.add('ring-2', 'ring-primary-500', 'shadow-md');
            
            // Emit event for audio player sync
            window.dispatchEvent(new CustomEvent('sentence-selected', {
                detail: {
                    startTime: element.dataset.startTime,
                    endTime: element.dataset.endTime
                }
            }));
        },
        
        highlightCurrentSentiment(detail) {
            // Update UI based on current sentiment
            // This could highlight the current sentence or update indicators
        }
    }
}
</script>
@endpush

<style>
/* Custom scrollbar for transcript */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.25);
}

.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.25);
}

/* Smooth transitions for sentiment view */
.sentiment-sentence {
    transition: all 0.2s ease;
}

.sentiment-sentence:hover {
    transform: translateY(-1px);
}
</style>