<div class="space-y-2">
    @foreach($actions as $action)
        <a href="{{ $action['url'] }}" 
           @if($action['download'] ?? false) download @endif
           @if(!($action['download'] ?? false)) wire:navigate @endif
           class="flex items-center justify-between w-full px-4 py-3 text-sm font-medium text-left rounded-lg transition-all
                  {{ match($action['color']) {
                      'success' => 'bg-success-50 dark:bg-success-900/20 text-success-700 dark:text-success-400 hover:bg-success-100 dark:hover:bg-success-900/30',
                      'primary' => 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 hover:bg-primary-100 dark:hover:bg-primary-900/30',
                      'gray' => 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                      default => 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                  } }}">
            <span class="flex items-center gap-2">
                <x-dynamic-component :component="$action['icon']" class="w-5 h-5" />
                {{ $action['label'] }}
            </span>
            <x-heroicon-m-arrow-right class="w-4 h-4 opacity-50" />
        </a>
    @endforeach
    
    @if(empty($actions))
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
            Keine Aktionen verfügbar
        </p>
    @endif
</div>