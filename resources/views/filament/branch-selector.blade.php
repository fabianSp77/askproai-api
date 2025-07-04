<div class="relative" x-data="{ open: false }">
    <button 
        @click="open = !open"
        type="button"
        class="flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition"
    >
        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
        </svg>
        <span>{{ $currentBranchName }}</span>
        <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div 
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800"
        style="display: none;"
    >
        <div class="py-1">
            @if(count($branches) > 1)
                <a
                    href="{{ request()->url() }}?branch="
                    @click="open = false"
                    class="group flex w-full items-center px-4 py-2 text-sm {{ !$currentBranchId ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    <div class="flex-1 text-left">
                        <div class="font-semibold">🏢 Alle Filialen</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Zeige Daten aller Filialen</div>
                    </div>
                </a>
                <hr class="my-1 border-gray-200 dark:border-gray-700" />
            @endif

            @foreach($branches as $branch)
                <a
                    href="{{ request()->url() }}?branch={{ $branch->id }}"
                    @click="open = false"
                    class="group flex w-full items-center px-4 py-2 text-sm {{ $currentBranchId === $branch->id ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    <div class="flex-1 text-left">
                        <div class="font-medium">{{ $branch->name }}</div>
                        @if($branch->company)
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $branch->company->name }}</div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>