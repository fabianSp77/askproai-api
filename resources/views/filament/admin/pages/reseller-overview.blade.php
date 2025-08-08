<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Overview Stats --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $stats = $this->getResellerStats();
            @endphp
            
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-md bg-primary-500 text-white">
                                <x-heroicon-o-user-group class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Resellers</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['total_resellers'] }}</dd>
                                <dd class="text-xs text-green-600">{{ $stats['active_resellers'] }} active</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-md bg-green-500 text-white">
                                <x-heroicon-o-building-office class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Clients</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['total_clients'] }}</dd>
                                <dd class="text-xs text-blue-600">{{ $stats['avg_clients_per_reseller'] }} avg per reseller</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-md bg-yellow-500 text-white">
                                <x-heroicon-o-banknotes class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900">€{{ number_format($stats['total_revenue'], 2) }}</dd>
                                <dd class="text-xs text-gray-500">YTD</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-8 w-8 rounded-md bg-purple-500 text-white">
                                <x-heroicon-o-currency-euro class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Commission</dt>
                                <dd class="text-lg font-medium text-gray-900">€{{ number_format($stats['total_commission'], 2) }}</dd>
                                <dd class="text-xs text-gray-500">Earned by resellers</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reseller Hierarchy Visualization --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Reseller Network</h3>
                    <div class="flex space-x-2">
                        <a href="{{ route('filament.admin.resources.resellers.index') }}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <x-heroicon-o-eye class="h-4 w-4 mr-1" />
                            View All Resellers
                        </a>
                        <a href="{{ route('filament.admin.resources.resellers.create') }}" 
                           class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <x-heroicon-o-plus class="h-4 w-4 mr-1" />
                            Add Reseller
                        </a>
                    </div>
                </div>

                <div class="space-y-4">
                    @php
                        $hierarchy = $this->getResellerHierarchy();
                    @endphp
                    
                    @forelse($hierarchy as $reseller)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            {{-- Reseller Header --}}
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-10 w-10 rounded-full {{ $reseller['data']['is_active'] ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-400' }}">
                                            <x-heroicon-o-user-group class="h-5 w-5" />
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900">{{ $reseller['data']['name'] }}</h4>
                                        <div class="flex items-center space-x-2 text-sm text-gray-500">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $reseller['data']['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $reseller['data']['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                            @if($reseller['data']['is_white_label'])
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    White Label
                                                </span>
                                            @endif
                                            <span>{{ $reseller['data']['commission_rate'] ?? 0 }}% Commission</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <div class="text-center">
                                        <div class="text-lg font-semibold text-gray-900">{{ count($reseller['data']['clients']) }}</div>
                                        <div>Clients</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-lg font-semibold text-green-600">€{{ number_format($reseller['metrics']['revenue_ytd'], 0) }}</div>
                                        <div>Revenue</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-lg font-semibold text-primary-600">€{{ number_format($reseller['metrics']['commission_earned'], 0) }}</div>
                                        <div>Commission</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Client Companies --}}
                            @if(count($reseller['data']['clients']) > 0)
                                <div class="ml-13">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($reseller['data']['clients'] as $client)
                                            <div class="bg-gray-50 rounded-lg p-3 border-l-4 {{ $client['is_active'] ? 'border-green-400' : 'border-red-400' }}">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <h5 class="font-medium text-gray-900">{{ $client['name'] }}</h5>
                                                        <p class="text-xs text-gray-500">{{ $client['industry'] ?? 'General' }}</p>
                                                    </div>
                                                    <div class="text-right text-xs text-gray-500">
                                                        <div>{{ $client['branches_count'] }} branches</div>
                                                        <div>{{ $client['staff_count'] }} staff</div>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex justify-between text-xs">
                                                    <span class="text-gray-500">{{ $client['customers_count'] }} customers</span>
                                                    <span class="text-gray-500">{{ $client['appointments_count'] }} appointments</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="ml-13 text-gray-500 text-sm italic">
                                    No clients yet
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <x-heroicon-o-user-group class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No resellers</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating your first reseller.</p>
                            <div class="mt-6">
                                <a href="{{ route('filament.admin.resources.resellers.create') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <x-heroicon-o-plus class="h-4 w-4 mr-1" />
                                    Add Reseller
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top Performers --}}
        @php
            $topPerformers = $this->getTopPerformers();
        @endphp
        
        @if(count($topPerformers) > 0)
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Top Performing Resellers</h3>
                    <div class="space-y-3">
                        @foreach($topPerformers as $index => $performer)
                            @php
                                $reseller = $performer['reseller'];
                                $metrics = $performer['metrics'];
                            @endphp
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-primary-100 text-primary-600 text-sm font-semibold">
                                            {{ $index + 1 }}
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $reseller->name }}</h4>
                                        <p class="text-sm text-gray-500">{{ $metrics['total_clients'] }} clients • {{ $reseller->commission_rate ?? 0 }}% commission</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-6 text-sm">
                                    <div class="text-center">
                                        <div class="font-semibold text-green-600">€{{ number_format($metrics['revenue_ytd'], 0) }}</div>
                                        <div class="text-gray-500">Revenue</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-semibold text-primary-600">€{{ number_format($metrics['commission_earned'], 0) }}</div>
                                        <div class="text-gray-500">Commission</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-semibold text-yellow-600">{{ number_format($metrics['client_retention_rate'], 1) }}%</div>
                                        <div class="text-gray-500">Retention</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>