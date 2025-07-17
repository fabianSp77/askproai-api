<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">Kundenverwaltung - Multi-Company Übersicht</h2>
                    <p class="text-sm text-gray-500 mt-1">Verwalten Sie alle Ihre Kunden zentral</p>
                </div>
                <div class="flex gap-2">
                    <x-filament::button
                        href="/admin/business-portal-admin"
                        tag="a"
                        icon="heroicon-o-building-office-2"
                        size="sm"
                    >
                        Alle Kunden verwalten
                    </x-filament::button>
                </div>
            </div>
        </x-slot>

        {{-- Summary Stats --}}
        @php
            $stats = $this->getTotalStats();
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-primary-50 dark:bg-primary-900/10 rounded-lg p-4">
                <div class="text-sm text-primary-600 dark:text-primary-400">Gesamt Kunden</div>
                <div class="text-2xl font-bold">{{ $stats['total_companies'] }}</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/10 rounded-lg p-4">
                <div class="text-sm text-green-600 dark:text-green-400">Aktiv heute</div>
                <div class="text-2xl font-bold">{{ $stats['active_today'] }}</div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/10 rounded-lg p-4">
                <div class="text-sm text-blue-600 dark:text-blue-400">Anrufe heute</div>
                <div class="text-2xl font-bold">{{ $stats['total_calls_today'] }}</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/10 rounded-lg p-4">
                <div class="text-sm text-purple-600 dark:text-purple-400">Umsatz heute</div>
                <div class="text-2xl font-bold">{{ number_format($stats['total_revenue_today'], 2, ',', '.') }} €</div>
            </div>
        </div>

        {{-- Top Companies Table --}}
        <div>
            <h3 class="text-lg font-semibold mb-3">Top 5 Aktive Kunden</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Kunde
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Standorte
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Nutzer
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Guthaben
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Heute
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Monat
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Aktionen
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->getCompaniesData() as $company)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-4 py-3">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $company['name'] }}
                                        </div>
                                        @if($company['is_low_balance'])
                                            <div class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1 mt-1">
                                                <x-heroicon-o-exclamation-triangle class="w-3 h-3" />
                                                Niedriges Guthaben
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                        {{ $company['branches'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                                        {{ $company['portal_users'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <span class="font-medium {{ $company['balance'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ number_format($company['balance'], 2, ',', '.') }} €
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <div class="flex items-center justify-center gap-3">
                                        <div class="flex items-center gap-1">
                                            <x-heroicon-o-phone class="w-4 h-4 text-gray-400" />
                                            <span>{{ $company['calls_today'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                                            <span>{{ $company['appointments_today'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <div class="text-xs">
                                        <div>{{ $company['monthly_calls'] }} Anrufe</div>
                                        <div class="text-gray-500">{{ $company['monthly_minutes'] }} Min</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-filament::button
                                            wire:click="$dispatch('openPortal', { companyId: {{ $company['id'] }} })"
                                            size="xs"
                                            outlined
                                            icon="heroicon-m-arrow-top-right-on-square"
                                        >
                                            Portal
                                        </x-filament::button>
                                        <x-filament::button
                                            href="/admin/companies/{{ $company['id'] }}"
                                            tag="a"
                                            size="xs"
                                            color="gray"
                                            outlined
                                            icon="heroicon-m-pencil"
                                        >
                                            Bearbeiten
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::button
                    href="/admin/companies/create"
                    tag="a"
                    icon="heroicon-o-plus-circle"
                    color="success"
                    class="w-full"
                >
                    Neuen Kunden anlegen
                </x-filament::button>
                
                <x-filament::button
                    href="/admin/business-portal-admin"
                    tag="a"
                    icon="heroicon-o-currency-euro"
                    color="warning"
                    outlined
                    class="w-full"
                >
                    Guthaben verwalten
                </x-filament::button>
                
                <x-filament::button
                    href="/admin/calls?tableFilters[date_range][created_at][from]={{ now()->startOfDay()->format('Y-m-d') }}"
                    tag="a"
                    icon="heroicon-o-chart-bar"
                    color="info"
                    outlined
                    class="w-full"
                >
                    Heutige Aktivitäten
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('openPortal', (event) => {
                // Redirect to BusinessPortalAdmin and trigger portal opening
                window.location.href = '/admin/business-portal-admin?open_company=' + event.companyId;
            });
        });
    </script>
</x-filament-widgets::widget>