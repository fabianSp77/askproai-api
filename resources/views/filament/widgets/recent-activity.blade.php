<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center">
                <x-heroicon-o-clock class="w-5 h-5 mr-2"/>
                Recent Activity
            </div>
        </x-slot>
        
        <div class="space-y-3">
            @forelse($this->getRecentActivities() as $activity)
                <div class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex-shrink-0">
                        <div class="p-2 rounded-full {{ $activity['icon_color'] === 'primary' ? 'bg-primary-100' : 'bg-success-100' }}">
                            <x-dynamic-component
                                :component="$activity['icon']"
                                class="w-4 h-4 {{ $activity['icon_color'] === 'primary' ? 'text-primary-600' : 'text-success-600' }}"
                            />
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $activity['title'] }}
                        </p>
                        <p class="text-sm text-gray-500 truncate">
                            {{ $activity['description'] }}
                        </p>
                        @if($activity['meta'])
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $activity['meta'] }}
                            </p>
                        @endif
                    </div>
                    <div class="flex-shrink-0 text-xs text-gray-400">
                        {{ $activity['time'] }}
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <x-heroicon-o-inbox class="mx-auto h-12 w-12 text-gray-400"/>
                    <p class="mt-2 text-sm text-gray-500">No recent activity</p>
                </div>
            @endforelse
        </div>
        
        @if($this->getRecentActivities()->isNotEmpty())
            <div class="mt-4 pt-4 border-t">
                <a href="{{ route('filament.admin.resources.appointments.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    View all activity →
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>