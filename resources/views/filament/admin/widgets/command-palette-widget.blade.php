<x-filament-widgets::widget class="fi-global-search-widget">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            @livewire('global-search')
        </div>
        
        <!-- Quick Stats -->
        <div class="hidden lg:flex items-center space-x-6 ml-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    {{ \App\Models\Call::whereDate('created_at', today())->count() }}
                </div>
                <div class="text-xs text-gray-500">Anrufe heute</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    {{ \App\Models\Appointment::whereDate('starts_at', today())->count() }}
                </div>
                <div class="text-xs text-gray-500">Termine heute</div>
            </div>
            
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    {{ \App\Models\Customer::whereDate('created_at', '>=', now()->subDays(30))->count() }}
                </div>
                <div class="text-xs text-gray-500">Neue Kunden (30T)</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>

<style>
    .fi-global-search-widget {
        @apply -mx-6 -mt-6 mb-6 bg-white border-b;
    }
    .fi-global-search-widget > div {
        @apply px-6 py-4;
    }
</style>