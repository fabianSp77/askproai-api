<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
        <button type="button" 
                onclick="this.closest('.fi-section').querySelector('.fi-section-content-ctn').classList.toggle('hidden')"
                class="fi-section-header-heading flex-1 flex items-center justify-between cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 -mx-6 px-6 -my-4 py-4">
            <div>
                <h3 class="fi-section-header-title text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Transkript
                </h3>
                <p class="fi-section-header-description text-sm text-gray-600 dark:text-gray-400">
                    Vollständiger Gesprächsverlauf
                </p>
            </div>
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>
    <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10 hidden">
        <div class="fi-section-content p-6">
            <div class="max-h-96 overflow-y-auto p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                @php
                    $formattedTranscript = nl2br(e($transcript));
                    // Highlight Agent and User parts
                    $formattedTranscript = preg_replace('/\b(Agent|AI|Assistant):/i', '<div class="mt-3 mb-1"><span class="inline-flex px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-md">$1</span></div>', $formattedTranscript);
                    $formattedTranscript = preg_replace('/\b(User|Kunde|Customer|Caller):/i', '<div class="mt-3 mb-1"><span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-md">$1</span></div>', $formattedTranscript);
                @endphp
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    {!! $formattedTranscript !!}
                </div>
            </div>
        </div>
    </div>
</div>