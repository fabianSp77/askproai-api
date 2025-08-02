@props([
    'navigation',
])

<header class="fi-topbar sticky top-0 z-20 overflow-x-clip">
    <nav class="flex h-16 items-center gap-x-4 bg-white px-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 md:px-6 lg:px-8">
        {{-- Mobile menu toggle --}}
        <x-mobile-menu-toggle />

        {{-- Brand --}}
        <div class="flex flex-1 items-center gap-x-4">
            @if ($homeUrl = filament()->getHomeUrl())
                <a href="{{ $homeUrl }}" class="block">
            @endif

            <div class="flex items-center gap-x-2">
                @if (filled($brandLogo = filament()->getBrandLogo()))
                    <img
                        src="{{ $brandLogo }}"
                        alt="{{ filament()->getBrandName() }}"
                        class="h-8 w-auto"
                    />
                @endif

                @if (filled($brandName = filament()->getBrandName()))
                    <span class="text-xl font-semibold leading-tight tracking-tight text-gray-950 dark:text-white">
                        {{ $brandName }}
                    </span>
                @endif
            </div>

            @if ($homeUrl)
                </a>
            @endif
        </div>

        {{-- Global search --}}
        @if (filament()->isGlobalSearchEnabled())
            <div class="ms-auto hidden md:block">
                @livewire(Filament\Livewire\GlobalSearch::class)
            </div>
        @endif

        {{-- User menu --}}
        <x-filament-panels::user-menu />
    </nav>
</header>