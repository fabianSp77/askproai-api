<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <h2 class="text-lg font-semibold">API Status Übersicht</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->getStatuses() as $service => $status)
                    <div class="p-4 rounded-lg border {{ $status['status'] === 'online' ? 'bg-green-50 border-green-200' : ($status['status'] === 'warning' ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200') }}">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium">{{ $service }}</h3>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status['status'] === 'online' ? 'bg-green-100 text-green-800' : ($status['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($status['status']) }}
                            </span>
                        </div>
                        
                        <p class="mt-2 text-sm text-gray-600">{{ $status['message'] }}</p>
                        
                        @if($status['latency'])
                            <p class="mt-1 text-xs text-gray-500">Antwortzeit: {{ $status['latency'] }}ms</p>
                        @endif
                        
                        @if(isset($status['stats']))
                            <div class="mt-2 text-xs text-gray-500">
                                <p>Anrufe: {{ $status['stats']['calls'] }}</p>
                                <p>Termine: {{ $status['stats']['appointments'] }}</p>
                            </div>
                        @endif
                        
                        @if(isset($status['metrics']))
                            <div class="mt-2 text-xs text-gray-500">
                                <p>Load: {{ $status['metrics']['load'] }}</p>
                                <p>Disk: {{ $status['metrics']['disk_usage'] }}</p>
                                <p>Memory: {{ $status['metrics']['memory'] }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <div class="text-xs text-gray-400 text-right">
                Letzte Aktualisierung: {{ now()->format('d.m.Y H:i:s') }}
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
