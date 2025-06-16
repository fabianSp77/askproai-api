<div class="w-full p-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
        <audio 
            controls 
            class="w-full mb-4"
            preload="metadata"
        >
            <source src="{{ $url }}" type="audio/mpeg">
            <source src="{{ $url }}" type="audio/wav">
            Ihr Browser unterstützt keine Audio-Wiedergabe.
        </audio>
        
        <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
            <span>Dauer: {{ gmdate('i:s', $duration) }}</span>
            <a 
                href="{{ $url }}" 
                download 
                class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Download
            </a>
        </div>
    </div>
</div>