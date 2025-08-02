<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Quick Stats Cards --}}
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-primary-600">{{ $metrics['users'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Total Users</div>
            </div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-success-600">{{ $metrics['appointments'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Today's Appointments</div>
            </div>
        </x-filament::card>
        
        <x-filament::card>
            <div class="text-center">
                <div class="text-3xl font-bold text-warning-600">{{ $metrics['calls'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Today's Calls</div>
            </div>
        </x-filament::card>
    </div>
    
    {{-- Manual Refresh Button --}}
    <div class="mt-6 text-center">
        <x-filament::button wire:click="refresh" size="sm">
            <x-heroicon-m-arrow-path class="w-4 h-4 mr-2" />
            Refresh Metrics
        </x-filament::button>
        
        @if(isset($metrics['timestamp']))
            <div class="text-xs text-gray-500 mt-2">
                Last updated: {{ $metrics['timestamp'] }}
            </div>
        @endif
    </div>
    
    {{-- Performance Note --}}
    <div class="mt-8">
        <x-filament::section>
            <x-slot name="heading">
                Performance Optimizations
            </x-slot>
            
            <div class="space-y-2 text-sm">
                <div class="flex items-center text-success-600">
                    <x-heroicon-m-check-circle class="w-5 h-5 mr-2" />
                    No auto-refresh or polling
                </div>
                <div class="flex items-center text-success-600">
                    <x-heroicon-m-check-circle class="w-5 h-5 mr-2" />
                    Minimal database queries
                </div>
                <div class="flex items-center text-success-600">
                    <x-heroicon-m-check-circle class="w-5 h-5 mr-2" />
                    Manual refresh only
                </div>
                <div class="flex items-center text-success-600">
                    <x-heroicon-m-check-circle class="w-5 h-5 mr-2" />
                    No heavy widgets loading
                </div>
            </div>
        </x-filament::section>
    </div>
    
    @push('scripts')
    <script>
        // Listen for manual refresh completion
        window.addEventListener('metrics-updated', () => {
            console.log('Metrics refreshed');
        });
    </script>
    @endpush
</x-filament-panels::page>