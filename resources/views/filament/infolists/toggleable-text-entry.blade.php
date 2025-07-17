<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @php
        $state = $getState();
        $content = $state['content'] ?? ['original' => '', 'translated' => ''];
        $showToggle = $state['showToggle'] ?? false;
        $shouldTranslate = $content['should_translate'] ?? false;
        $sourceLanguage = $content['source_language'] ?? null;
        $targetLanguage = $content['target_language'] ?? null;
    @endphp

    <div 
        x-data="{ 
            showTranslated: {{ $shouldTranslate ? 'true' : 'false' }},
            original: @js($content['original']),
            translated: @js($content['translated'])
        }" 
        class="toggleable-text-container"
    >
        <div class="relative">
            {{-- Text Content --}}
            <div 
                x-text="showTranslated ? translated : original"
                class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300"
            ></div>
            
            {{-- Toggle Button --}}
            @if($showToggle && $shouldTranslate && $content['original'] !== $content['translated'])
                <div class="mt-2 flex items-center gap-2">
                    <button
                        type="button"
                        x-on:click="showTranslated = !showTranslated"
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
                    
                    @if($sourceLanguage && $targetLanguage)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            <span x-show="!showTranslated">{{ strtoupper($sourceLanguage) }}</span>
                            <span x-show="showTranslated">{{ strtoupper($targetLanguage) }}</span>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-dynamic-component>