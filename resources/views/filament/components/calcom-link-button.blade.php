<div class="w-full">
    <a href="{{ $url }}" 
       target="_blank"
       class="inline-flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-colors">
        <div class="flex items-center space-x-2">
            <x-heroicon-o-arrow-top-right-on-square class="w-5 h-5 text-gray-400" />
            <span>{{ $sectionName }} in Cal.com öffnen</span>
        </div>
        <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-400" />
    </a>
    @if($instructions)
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            {{ $instructions }}
        </p>
    @endif
</div>