<div class="bg-white dark:bg-gray-800 rounded-lg">
    {{-- Header --}}
    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Gesprächstranskript
                    </h3>
                    <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>
                            <strong>{{ $wordCount }}</strong> Wörter
                        </span>
                        <span>•</span>
                        <span>
                            <strong>{{ $readingTime }}</strong> Min. Lesezeit
                        </span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Search --}}
                <div class="relative">
                    <input type="text"
                           id="transcript-search"
                           placeholder="Suchen..."
                           class="pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600
                                  bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                  rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                  w-32 sm:w-40"
                           onkeyup="searchTranscript(this.value)">
                    <svg class="absolute left-2.5 top-2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Copy Button --}}
                <button onclick="copyTranscript()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5
                               text-sm font-medium text-gray-700 dark:text-gray-300
                               bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600
                               rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
                               transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="copy-button-text">Kopieren</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Transcript Content --}}
    <div id="transcript-content" class="px-4 sm:px-6 py-4 max-h-[50vh] md:max-h-[60vh] overflow-y-auto">
        <div class="prose prose-sm dark:prose-invert max-w-none">
            <pre class="whitespace-pre-wrap text-sm font-normal text-gray-900 dark:text-gray-100">{!! nl2br(e($text)) !!}</pre>
        </div>
    </div>
</div>

<script>
function searchTranscript(query) {
    const content = document.getElementById('transcript-content');
    const text = content.querySelector('pre');
    const originalText = text.textContent;

    // Remove previous highlights
    text.innerHTML = originalText.replace(/<mark[^>]*>(.*?)<\/mark>/gi, '$1');

    if (query.length > 0) {
        const regex = new RegExp(`(${query})`, 'gi');
        text.innerHTML = text.innerHTML.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-700 text-gray-900 dark:text-gray-100 px-0.5 rounded">$1</mark>');
    }
}

function copyTranscript() {
    const content = document.getElementById('transcript-content');
    const text = content.querySelector('pre').textContent;

    navigator.clipboard.writeText(text).then(() => {
        const button = event.currentTarget;
        const buttonText = button.querySelector('.copy-button-text');
        const originalText = buttonText.textContent;

        buttonText.textContent = 'Kopiert!';
        button.classList.add('bg-green-50', 'dark:bg-green-900', 'border-green-500');

        setTimeout(() => {
            buttonText.textContent = originalText;
            button.classList.remove('bg-green-50', 'dark:bg-green-900', 'border-green-500');
        }, 2000);
    });
}
</script>

<style>
/* Custom scrollbar for transcript */
#transcript-content::-webkit-scrollbar {
    width: 6px;
}

#transcript-content::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
}

#transcript-content::-webkit-scrollbar-thumb {
    background: #9ca3af;
    border-radius: 3px;
}

#transcript-content::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Dark mode scrollbar */
.dark #transcript-content::-webkit-scrollbar-track {
    background: #374151;
}

.dark #transcript-content::-webkit-scrollbar-thumb {
    background: #6b7280;
}

.dark #transcript-content::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>