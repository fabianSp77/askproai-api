<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section with Branding --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    @if($this->record->logo)
                        <img src="{{ asset($this->record->logo) }}" alt="{{ $this->record->name }}" class="h-16 w-16 rounded-lg object-cover">
                    @else
                        <div class="h-16 w-16 rounded-lg bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <x-heroicon-o-building-office-2 class="h-8 w-8 text-primary-500" />
                        </div>
                    @endif
                    
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->record->name }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $this->record->email }}</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    @if($this->record->is_white_label)
                        <span class="inline-flex items-center rounded-full bg-purple-50 dark:bg-purple-900/20 px-3 py-1 text-sm font-medium text-purple-700 dark:text-purple-300">
                            <x-heroicon-m-paint-brush class="mr-1.5 h-4 w-4" />
                            White Label
                        </span>
                    @endif
                    
                    @if($this->record->is_active)
                        <span class="inline-flex items-center rounded-full bg-green-50 dark:bg-green-900/20 px-3 py-1 text-sm font-medium text-green-700 dark:text-green-300">
                            <x-heroicon-m-check-circle class="mr-1.5 h-4 w-4" />
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/20 px-3 py-1 text-sm font-medium text-red-700 dark:text-red-300">
                            <x-heroicon-m-x-circle class="mr-1.5 h-4 w-4" />
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- White Label Preview Section --}}
        @if($this->record->is_white_label)
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <x-heroicon-o-paint-brush class="h-5 w-5 mr-2" />
                    White Label Branding Preview
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Custom Colors</p>
                        <div class="flex gap-3">
                            <div class="text-center">
                                <div class="h-12 w-12 rounded-lg shadow-inner" style="background-color: {{ $this->record->settings['brand_primary_color'] ?? '#3B82F6' }}"></div>
                                <p class="text-xs text-gray-500 mt-1">Primary</p>
                            </div>
                            <div class="text-center">
                                <div class="h-12 w-12 rounded-lg shadow-inner" style="background-color: {{ $this->record->settings['brand_secondary_color'] ?? '#8B5CF6' }}"></div>
                                <p class="text-xs text-gray-500 mt-1">Secondary</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Custom Domain</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $this->record->settings['custom_domain'] ?? 'Not configured' }}
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 flex justify-end">
                    <x-filament::link href="{{ \App\Filament\Admin\Resources\ResellerResource::getUrl('edit', ['record' => $this->record]) }}" icon="heroicon-m-pencil-square" size="sm">
                        Edit Branding
                    </x-filament::link>
                </div>
            </div>
        @endif
        
        {{-- Performance Widgets --}}
        <div class="grid grid-cols-1 gap-6">
            @livewire(\App\Filament\Admin\Resources\ResellerResource\Widgets\ResellerPerformanceWidget::class, ['record' => $this->record])
        </div>
        
        {{-- Revenue Chart --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                @livewire(\App\Filament\Admin\Resources\ResellerResource\Widgets\ResellerRevenueChart::class, ['record' => $this->record])
            </div>
            
            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                
                <div class="space-y-3">
                    <a href="{{ \App\Filament\Admin\Resources\CompanyResource::getUrl('create', ['parent_company_id' => $this->record->id]) }}" class="block">
                        <x-filament::button icon="heroicon-o-plus" size="sm" color="primary" class="w-full">
                            Add New Client
                        </x-filament::button>
                    </a>
                    
                    <a href="{{ \App\Filament\Admin\Resources\PricingTierResource::getUrl('create', ['company_id' => $this->record->id]) }}" class="block">
                        <x-filament::button icon="heroicon-o-currency-euro" size="sm" color="success" outlined class="w-full">
                            Configure Pricing
                        </x-filament::button>
                    </a>
                    
                    <a href="{{ \App\Filament\Admin\Resources\InvoiceResource::getUrl('index', ['tableFilters' => ['company_id' => ['value' => $this->record->id]]]) }}" class="block">
                        <x-filament::button icon="heroicon-o-document-text" size="sm" color="gray" outlined class="w-full">
                            View Invoices
                        </x-filament::button>
                    </a>
                    
                    <x-filament::button wire:click="exportReport" icon="heroicon-o-arrow-down-tray" size="sm" color="gray" outlined class="w-full">
                        Export Report
                    </x-filament::button>
                </div>
            </div>
        </div>
        
        {{-- Clients Table --}}
        <div>
            @livewire(\App\Filament\Admin\Resources\ResellerResource\Widgets\ResellerClientsTable::class, ['record' => $this->record])
        </div>
    </div>
</x-filament-panels::page>