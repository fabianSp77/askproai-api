<x-filament-panels::page>
    <form wire:submit="calculate">
        {{ $this->form }}
        
        <div class="mt-6 flex gap-4">
            <x-filament::button type="submit" icon="heroicon-o-calculator">
                Berechnen
            </x-filament::button>
            
            @if(!empty($this->data['total']))
                <x-filament::button 
                    color="success" 
                    icon="heroicon-o-document-text"
                    wire:click="generateQuote"
                >
                    Angebot erstellen
                </x-filament::button>
            @endif
        </div>
    </form>
    
    @if(!empty($this->data['total']))
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Kostenübersicht --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Kostenübersicht</h3>
                
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">Paket:</dt>
                        <dd class="font-medium">{{ $this->data['package_details']['name'] }}</dd>
                    </div>
                    
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">Grundpreis:</dt>
                        <dd>€ {{ number_format($this->data['base_price'], 2, ',', '.') }}</dd>
                    </div>
                    
                    @if($this->data['overage_minutes'] > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">{{ $this->data['overage_minutes'] }} Zusatzminuten:</dt>
                            <dd>€ {{ number_format($this->data['overage_cost'], 2, ',', '.') }}</dd>
                        </div>
                    @endif
                    
                    <div class="pt-2 border-t">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Zwischensumme:</dt>
                            <dd>€ {{ number_format($this->data['subtotal'], 2, ',', '.') }}</dd>
                        </div>
                    </div>
                    
                    @if($this->data['tax_rate'] > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">MwSt. ({{ $this->data['tax_rate'] }}%):</dt>
                            <dd>€ {{ number_format($this->data['tax_amount'], 2, ',', '.') }}</dd>
                        </div>
                    @else
                        <div class="text-sm text-gray-500">
                            Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.
                        </div>
                    @endif
                    
                    <div class="pt-2 border-t">
                        <div class="flex justify-between text-lg font-semibold">
                            <dt>Gesamtpreis:</dt>
                            <dd class="text-primary-600">€ {{ number_format($this->data['total'], 2, ',', '.') }}</dd>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">pro Monat</div>
                    </div>
                </dl>
            </div>
            
            {{-- ROI Berechnung --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">ROI-Berechnung</h3>
                
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">Gesparte Stunden:</dt>
                        <dd class="font-medium">{{ number_format($this->data['hours_saved'], 1, ',', '.') }} Std/Monat</dd>
                    </div>
                    
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">Mitarbeiterkosten (€25/Std):</dt>
                        <dd>€ {{ number_format($this->data['potential_savings'], 2, ',', '.') }}</dd>
                    </div>
                    
                    <div class="flex justify-between">
                        <dt class="text-gray-600 dark:text-gray-400">AskProAI Kosten:</dt>
                        <dd>- € {{ number_format($this->data['total'], 2, ',', '.') }}</dd>
                    </div>
                    
                    <div class="pt-2 border-t">
                        <div class="flex justify-between text-lg font-semibold">
                            <dt>Ersparnis:</dt>
                            <dd class="{{ $this->data['roi'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                € {{ number_format($this->data['roi'], 2, ',', '.') }}
                            </dd>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            {{ number_format(abs($this->data['roi_percentage']), 0) }}% 
                            {{ $this->data['roi'] > 0 ? 'Return on Investment' : 'zusätzliche Kosten' }}
                        </div>
                    </div>
                </dl>
                
                @if($this->data['roi'] > 0)
                    <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-sm text-green-800 dark:text-green-200">
                            <strong>Empfehlung:</strong> Sie sparen monatlich {{ number_format($this->data['roi'], 2, ',', '.') }} € 
                            durch den Einsatz von AskProAI.
                        </p>
                    </div>
                @endif
            </div>
            
            {{-- Paket-Features --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Inkludierte Features</h3>
                
                <ul class="space-y-2">
                    <li class="flex items-start">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                        <span class="text-sm">24/7 AI-Telefonassistent</span>
                    </li>
                    <li class="flex items-start">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                        <span class="text-sm">{{ $this->data['package_details']['included_minutes'] }} Inklusiv-Minuten</span>
                    </li>
                    <li class="flex items-start">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                        <span class="text-sm">Cal.com Integration</span>
                    </li>
                    <li class="flex items-start">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                        <span class="text-sm">E-Mail Benachrichtigungen</span>
                    </li>
                    
                    @if($this->data['package'] === 'professional' || $this->data['package'] === 'enterprise')
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                            <span class="text-sm">Mehrere Standorte</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                            <span class="text-sm">Priority Support</span>
                        </li>
                    @endif
                    
                    @if($this->data['package'] === 'enterprise')
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                            <span class="text-sm">API Zugang</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                            <span class="text-sm">Dedizierter Account Manager</span>
                        </li>
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                            <span class="text-sm">SLA Garantie</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        
        {{-- Vergleichstabelle --}}
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Paketvergleich bei {{ $this->data['estimated_minutes'] }} Minuten/Monat</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Paket</th>
                            <th class="text-right py-2">Grundpreis</th>
                            <th class="text-right py-2">Inkl. Minuten</th>
                            <th class="text-right py-2">Zusatzminuten</th>
                            <th class="text-right py-2">Preis/Min</th>
                            <th class="text-right py-2">Gesamtpreis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $allPackages = [
                                'starter' => ['name' => 'Starter', 'base_price' => 49, 'included_minutes' => 100, 'overage_price' => 0.29],
                                'professional' => ['name' => 'Professional', 'base_price' => 149, 'included_minutes' => 500, 'overage_price' => 0.19],
                                'enterprise' => ['name' => 'Enterprise', 'base_price' => 499, 'included_minutes' => PHP_INT_MAX, 'overage_price' => 0],
                            ];
                        @endphp
                        
                        @foreach($allPackages as $key => $package)
                            @php
                                $overage = max(0, $this->data['estimated_minutes'] - $package['included_minutes']);
                                $overageCost = $overage * $package['overage_price'];
                                $total = $package['base_price'] + $overageCost;
                                if (!$this->data['is_small_business']) {
                                    $total *= 1.19;
                                }
                            @endphp
                            <tr class="border-b {{ $key === $this->data['package'] ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}">
                                <td class="py-2 font-medium">
                                    {{ $package['name'] }}
                                    @if($key === $this->data['package'])
                                        <span class="text-xs text-primary-600 ml-2">(Ausgewählt)</span>
                                    @endif
                                </td>
                                <td class="text-right py-2">€ {{ number_format($package['base_price'], 2, ',', '.') }}</td>
                                <td class="text-right py-2">{{ $package['included_minutes'] === PHP_INT_MAX ? '∞' : $package['included_minutes'] }}</td>
                                <td class="text-right py-2">{{ $overage > 0 ? $overage : '-' }}</td>
                                <td class="text-right py-2">€ {{ number_format($package['overage_price'], 2, ',', '.') }}</td>
                                <td class="text-right py-2 font-semibold">€ {{ number_format($total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>