<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Company Selector --}}
        <div>
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>
        
        {{-- Company Stats --}}
        @if($companyStats)
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h2 class="text-lg font-semibold mb-4">{{ $companyStats['company']->name }} - Übersicht</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    {{-- Current Balance --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-500">Aktuelles Guthaben</div>
                        <div class="text-2xl font-bold {{ $companyStats['effective_balance'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($companyStats['effective_balance'], 2, ',', '.') }} €
                        </div>
                        @if($companyStats['balance'] && $companyStats['balance']->reserved_balance > 0)
                            <div class="text-xs text-gray-500">
                                ({{ number_format($companyStats['balance']->reserved_balance, 2, ',', '.') }} € reserviert)
                            </div>
                        @endif
                    </div>
                    
                    {{-- Portal Users --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-500">Portal Nutzer</div>
                        <div class="text-2xl font-bold">{{ $companyStats['portal_users'] }}</div>
                    </div>
                    
                    {{-- Monthly Usage --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-500">Nutzung diesen Monat</div>
                        <div class="text-lg font-semibold">{{ $companyStats['monthly_usage']['calls'] }} Anrufe</div>
                        <div class="text-sm text-gray-600">
                            {{ $companyStats['monthly_usage']['minutes'] }} Min. / 
                            {{ number_format($companyStats['monthly_usage']['charges'], 2, ',', '.') }} €
                        </div>
                    </div>
                    
                    {{-- Last Top-up --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-500">Letzte Aufladung</div>
                        @if($companyStats['last_topup'])
                            <div class="text-lg font-semibold text-green-600">
                                +{{ number_format($companyStats['last_topup']['amount'], 2, ',', '.') }} €
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $companyStats['last_topup']['date']->format('d.m.Y H:i') }}
                            </div>
                        @else
                            <div class="text-sm text-gray-400">Keine Aufladung</div>
                        @endif
                    </div>
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex gap-4">
                    <x-filament::button
                        wire:click="openCustomerPortal"
                        icon="heroicon-o-arrow-top-right-on-square"
                    >
                        Kundenportal öffnen
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="adjustBalance"
                        color="gray"
                        icon="heroicon-o-currency-euro"
                    >
                        Guthaben anpassen
                    </x-filament::button>
                </div>
            </div>
            
            {{-- Recent Transactions --}}
            @if($recentTransactions && count($recentTransactions) > 0)
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Letzte Transaktionen</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Beschreibung</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Typ</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Betrag</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Guthaben</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($recentTransactions as $transaction)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($transaction['created_at'])->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $transaction['description'] }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-center">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $transaction['type'] === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $transaction['type'] === 'credit' ? 'Aufladung' : 'Verbrauch' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right font-medium
                                            {{ $transaction['type'] === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transaction['type'] === 'credit' ? '+' : '-' }}{{ number_format($transaction['amount'], 2, ',', '.') }} €
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 text-right">
                                            {{ number_format($transaction['balance_after'], 2, ',', '.') }} €
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
        
        {{-- All Companies Table --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Alle Firmen mit Prepaid Billing</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Firma</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Guthaben</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Reserviert</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Verfügbar</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Portal Nutzer</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->getAllCompaniesData() as $company)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $company['name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $company['balance'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($company['balance'], 2, ',', '.') }} €
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">
                                        {{ number_format($company['reserved_balance'], 2, ',', '.') }} €
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-medium {{ $company['effective_balance'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($company['effective_balance'], 2, ',', '.') }} €
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $company['portal_users'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <x-filament::button
                                            wire:click="openPortalForCompany({{ $company['id'] }})"
                                            size="sm"
                                            outlined
                                        >
                                            Portal öffnen
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Balance Adjustment Modal --}}
    <x-filament::modal id="adjust-balance-modal" width="lg">
        <x-slot name="heading">
            Guthaben anpassen
        </x-slot>
        
        <form wire:submit.prevent="processBalanceAdjustment">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Typ</label>
                    <select wire:model="adjustmentType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="credit">Aufladung (+)</option>
                        <option value="debit">Abzug (-)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Betrag (€)</label>
                    <input type="number" wire:model="adjustmentAmount" step="0.01" min="0.01" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                    <textarea wire:model="adjustmentDescription" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                              placeholder="Manuelle Anpassung durch Admin"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-4">
                <x-filament::button type="button" color="gray" x-on:click="close">
                    Abbrechen
                </x-filament::button>
                
                <x-filament::button type="submit">
                    Guthaben anpassen
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</x-filament-panels::page>