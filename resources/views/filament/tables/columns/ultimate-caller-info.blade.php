<div class="flex items-center gap-3">
    <div class="relative">
        @if($getRecord()->customer)
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                {{ strtoupper(substr($getRecord()->customer->name, 0, 2)) }}
            </div>
            @if($getRecord()->customer->tags && in_array('VIP', $getRecord()->customer->tags))
                <div class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-400 rounded-full border-2 border-white animate-pulse"></div>
            @endif
        @else
            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                <x-heroicon-o-user class="w-5 h-5 text-gray-400" />
            </div>
        @endif
    </div>
    
    <div class="flex-1 min-w-0">
        <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
            {{ $getRecord()->customer?->name ?? 'Unbekannt' }}
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <x-heroicon-m-phone class="w-3 h-3" />
            <span class="font-mono">{{ $getRecord()->from_number }}</span>
            @if($getRecord()->customer?->email)
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <x-heroicon-m-envelope class="w-3 h-3" />
            @endif
        </div>
    </div>
    
    @if($getRecord()->customer)
        <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', ['record' => $getRecord()->customer]) }}" 
           class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
           title="Kunde anzeigen">
            <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
        </a>
    @endif
</div>