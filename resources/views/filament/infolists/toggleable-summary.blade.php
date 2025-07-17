@php
    use App\Helpers\AutoTranslateHelper;
    use App\Services\TranslationService;
    
    $record = $getRecord();
    $summary = $record->webhook_data['call_analysis']['call_summary'] ?? null;
    
    if (!$summary) {
        $displayText = '—';
        $showToggle = false;
    } else {
        // Detect language and get toggleable content
        $translator = app(TranslationService::class);
        $detectedLanguage = $translator->detectLanguage($summary);
        $userLanguage = auth()->user()->content_language ?? 'de';
        
        $toggleData = AutoTranslateHelper::getToggleableContent($summary, $detectedLanguage);
        
        $showToggle = $toggleData['should_translate'] && 
                      auth()->user()?->auto_translate_content &&
                      $detectedLanguage !== $userLanguage;
        
        $displayText = $showToggle ? $toggleData['translated'] : $summary;
    }
@endphp

<div x-data="{ 
    showOriginal: false,
    originalText: @js($summary ?? ''),
    translatedText: @js($showToggle ? $toggleData['translated'] : ''),
    sourceLanguage: @js($detectedLanguage ?? 'unknown'),
    targetLanguage: @js($userLanguage ?? 'de')
}" class="space-y-2">
    
    {{-- Main text display --}}
    <div class="prose prose-sm max-w-none dark:prose-invert">
        <div x-show="!showOriginal" x-html="translatedText || originalText"></div>
        <div x-show="showOriginal" x-html="originalText" style="display: none;"></div>
    </div>
    
    {{-- Toggle controls --}}
    @if($showToggle)
        <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129">
                    </path>
                </svg>
                <span x-show="!showOriginal">
                    Automatisch übersetzt von <strong x-text="sourceLanguage.toUpperCase()"></strong> 
                    nach <strong x-text="targetLanguage.toUpperCase()"></strong>
                </span>
                <span x-show="showOriginal" style="display: none;">
                    Originaltext in <strong x-text="sourceLanguage.toUpperCase()"></strong>
                </span>
            </div>
            
            <button 
                @click="showOriginal = !showOriginal"
                type="button"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md
                       text-gray-700 bg-white border border-gray-300 hover:bg-gray-50
                       dark:text-gray-200 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                       transition-colors duration-200"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4">
                    </path>
                </svg>
                <span x-show="!showOriginal">Original anzeigen</span>
                <span x-show="showOriginal" style="display: none;">Übersetzung anzeigen</span>
            </button>
        </div>
    @endif
</div>