@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Auto-Aufladung</h1>
            <p class="mt-1 text-sm text-gray-600">
                Konfigurieren Sie die automatische Aufladung Ihres Guthabens
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('business.billing.auto-topup.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Auto-Topup Toggle -->
            <div class="bg-white shadow sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="auto_topup_enabled" 
                                   name="auto_topup_enabled" 
                                   type="checkbox" 
                                   value="1"
                                   {{ $prepaidBalance->auto_topup_enabled ? 'checked' : '' }}
                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="auto_topup_enabled" class="font-medium text-gray-700">
                                Auto-Aufladung aktivieren
                            </label>
                            <p class="text-gray-500">
                                Ihr Guthaben wird automatisch aufgeladen, wenn es unter den definierten Schwellenwert fällt.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Settings -->
            <div id="auto-topup-settings" class="space-y-6 {{ !$prepaidBalance->auto_topup_enabled ? 'opacity-50' : '' }}">
                <!-- Threshold Amount -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Auflade-Schwellenwert
                        </h3>
                        <div class="max-w-xs">
                            <label for="auto_topup_threshold" class="block text-sm font-medium text-gray-700">
                                Guthaben fällt unter
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">€</span>
                                </div>
                                <input type="number" 
                                       name="auto_topup_threshold" 
                                       id="auto_topup_threshold" 
                                       min="10" 
                                       max="500" 
                                       step="5"
                                       value="{{ $prepaidBalance->auto_topup_threshold }}"
                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
                                       required
                                       {{ !$prepaidBalance->auto_topup_enabled ? 'disabled' : '' }}>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">EUR</span>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Empfohlen: 20-50 € je nach Nutzungsvolumen
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Topup Amount -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Aufladebetrag
                        </h3>
                        <div class="max-w-xs">
                            <label for="auto_topup_amount" class="block text-sm font-medium text-gray-700">
                                Betrag der aufgeladen wird
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">€</span>
                                </div>
                                <input type="number" 
                                       name="auto_topup_amount" 
                                       id="auto_topup_amount" 
                                       min="50" 
                                       max="5000" 
                                       step="10"
                                       value="{{ $prepaidBalance->auto_topup_amount }}"
                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
                                       required
                                       {{ !$prepaidBalance->auto_topup_enabled ? 'disabled' : '' }}>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">EUR</span>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Der Betrag wird Ihrem Guthaben hinzugefügt, wenn der Schwellenwert unterschritten wird.
                            </p>
                            
                            <!-- Show applicable bonus -->
                            @if($applicableBonus)
                            <div class="mt-3 p-3 bg-green-50 rounded-md">
                                <p class="text-sm text-green-800">
                                    <strong>Bonus:</strong> Bei diesem Betrag erhalten Sie {{ $applicableBonus['bonus_percentage'] }}% Bonus ({{ number_format($applicableBonus['bonus_amount'], 2, ',', '.') }} € zusätzlich)
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Limits -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Sicherheitslimits
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label for="auto_topup_daily_limit" class="block text-sm font-medium text-gray-700">
                                    Maximale Aufladungen pro Tag
                                </label>
                                <select name="auto_topup_daily_limit" 
                                        id="auto_topup_daily_limit" 
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                        {{ !$prepaidBalance->auto_topup_enabled ? 'disabled' : '' }}>
                                    <option value="1" {{ $prepaidBalance->auto_topup_daily_limit == 1 ? 'selected' : '' }}>1 Aufladung</option>
                                    <option value="2" {{ $prepaidBalance->auto_topup_daily_limit == 2 ? 'selected' : '' }}>2 Aufladungen</option>
                                    <option value="3" {{ $prepaidBalance->auto_topup_daily_limit == 3 ? 'selected' : '' }}>3 Aufladungen</option>
                                    <option value="5" {{ $prepaidBalance->auto_topup_daily_limit == 5 ? 'selected' : '' }}>5 Aufladungen</option>
                                </select>
                            </div>

                            <div>
                                <label for="auto_topup_monthly_limit" class="block text-sm font-medium text-gray-700">
                                    Maximale Aufladungen pro Monat
                                </label>
                                <select name="auto_topup_monthly_limit" 
                                        id="auto_topup_monthly_limit" 
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                        {{ !$prepaidBalance->auto_topup_enabled ? 'disabled' : '' }}>
                                    <option value="5" {{ $prepaidBalance->auto_topup_monthly_limit == 5 ? 'selected' : '' }}>5 Aufladungen</option>
                                    <option value="10" {{ $prepaidBalance->auto_topup_monthly_limit == 10 ? 'selected' : '' }}>10 Aufladungen</option>
                                    <option value="20" {{ $prepaidBalance->auto_topup_monthly_limit == 20 ? 'selected' : '' }}>20 Aufladungen</option>
                                    <option value="30" {{ $prepaidBalance->auto_topup_monthly_limit == 30 ? 'selected' : '' }}>30 Aufladungen</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Zahlungsmethode
                        </h3>
                        
                        @if($savedPaymentMethods->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($savedPaymentMethods as $pm)
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="payment_method_id" 
                                           value="{{ $pm->id }}"
                                           {{ $prepaidBalance->auto_topup_payment_method_id == $pm->id ? 'checked' : '' }}
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300"
                                           {{ !$prepaidBalance->auto_topup_enabled ? 'disabled' : '' }}>
                                    <span class="ml-3">
                                        <span class="block text-sm font-medium text-gray-700">
                                            {{ ucfirst($pm->card->brand) }} •••• {{ $pm->card->last4 }}
                                        </span>
                                        <span class="block text-sm text-gray-500">
                                            Läuft ab {{ $pm->card->exp_month }}/{{ $pm->card->exp_year }}
                                        </span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                            
                            <div class="mt-4">
                                <a href="{{ route('business.billing.payment-methods') }}" 
                                   class="text-sm text-indigo-600 hover:text-indigo-500">
                                    Zahlungsmethode hinzufügen oder verwalten
                                </a>
                            </div>
                        @else
                            <div class="text-center py-6">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Zahlungsmethode hinterlegt</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Fügen Sie eine Zahlungsmethode hinzu, um Auto-Aufladung zu aktivieren.
                                </p>
                                <div class="mt-3">
                                    <a href="{{ route('business.billing.payment-methods.add') }}" 
                                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Zahlungsmethode hinzufügen
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('business.billing.index') }}" 
                   class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Abbrechen
                </a>
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Einstellungen speichern
                </button>
            </div>
        </form>

        <!-- Information Box -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-sm font-medium text-blue-900 mb-2">So funktioniert Auto-Aufladung:</h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Wenn Ihr Guthaben unter den Schwellenwert fällt, wird automatisch der festgelegte Betrag aufgeladen</li>
                <li>• Die Aufladung erfolgt über Ihre hinterlegte Zahlungsmethode</li>
                <li>• Sie erhalten bei jeder Auto-Aufladung die gleichen Boni wie bei manuellen Aufladungen</li>
                <li>• Die Sicherheitslimits schützen vor unerwarteten Kosten</li>
                <li>• Sie können Auto-Aufladung jederzeit deaktivieren</li>
            </ul>
        </div>
    </div>
</div>

<script>
    // Toggle settings based on checkbox
    document.getElementById('auto_topup_enabled').addEventListener('change', function() {
        const settings = document.getElementById('auto-topup-settings');
        const inputs = settings.querySelectorAll('input, select');
        
        if (this.checked) {
            settings.classList.remove('opacity-50');
            inputs.forEach(input => input.disabled = false);
        } else {
            settings.classList.add('opacity-50');
            inputs.forEach(input => input.disabled = true);
        }
    });
    
    // Update bonus display when amount changes
    const bonusRules = @json($bonusRules);
    
    document.getElementById('auto_topup_amount').addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const bonusInfo = calculateBonus(amount);
        
        // Remove existing bonus info
        const existingBonus = this.parentElement.parentElement.querySelector('.bg-green-50');
        if (existingBonus) {
            existingBonus.remove();
        }
        
        // Add new bonus info if applicable
        if (bonusInfo.rule && bonusInfo.bonusAmount > 0) {
            const bonusDiv = document.createElement('div');
            bonusDiv.className = 'mt-3 p-3 bg-green-50 rounded-md';
            bonusDiv.innerHTML = `
                <p class="text-sm text-green-800">
                    <strong>Bonus:</strong> Bei diesem Betrag erhalten Sie ${bonusInfo.rule.bonus_percentage}% Bonus (${formatCurrency(bonusInfo.bonusAmount)} zusätzlich)
                </p>
            `;
            this.parentElement.parentElement.appendChild(bonusDiv);
        }
    });
    
    function calculateBonus(amount) {
        let applicableRule = null;
        let highestBonus = 0;
        
        bonusRules.forEach(rule => {
            if (amount >= rule.min_amount && (!rule.max_amount || amount <= rule.max_amount)) {
                let bonusAmount = amount * (rule.bonus_percentage / 100);
                if (rule.max_bonus_amount && bonusAmount > rule.max_bonus_amount) {
                    bonusAmount = rule.max_bonus_amount;
                }
                
                if (bonusAmount > highestBonus) {
                    highestBonus = bonusAmount;
                    applicableRule = rule;
                }
            }
        });
        
        return {
            rule: applicableRule,
            bonusAmount: highestBonus
        };
    }
    
    function formatCurrency(amount) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount).replace('€', '').trim() + ' €';
    }
</script>
@endsection