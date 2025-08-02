@extends('portal.layouts.unified')

@section('page-title', 'Guthaben aufladen')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Guthaben aufladen</h2>
            <p class="mt-1 text-sm text-gray-600">Wählen Sie einen Betrag aus oder geben Sie einen eigenen Betrag ein.</p>
        </div>

        <!-- Current Balance -->
        <div class="bg-white shadow rounded-lg mb-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Aktuelles Guthaben</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($company->balance ?? 0, 2, ',', '.') }} €</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Automatische Aufladung</p>
                    <p class="text-sm font-medium {{ $company->auto_topup_enabled ? 'text-green-600' : 'text-gray-600' }}">
                        {{ $company->auto_topup_enabled ? 'Aktiv' : 'Inaktiv' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Topup Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <form id="topup-form" action="{{ route('business.billing.topup.process') }}" method="POST">
                    @csrf
                    
                    <!-- Preset Amounts -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Schnellauswahl</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach([50, 100, 200, 500] as $amount)
                            <button type="button" 
                                    class="amount-button px-4 py-3 border border-gray-300 rounded-lg text-center hover:bg-blue-50 hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $suggestedAmount == $amount ? 'bg-blue-50 border-blue-500' : '' }}"
                                    data-amount="{{ $amount }}">
                                {{ $amount }} €
                            </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Custom Amount -->
                    <div class="mb-6">
                        <label for="custom-amount" class="block text-sm font-medium text-gray-700 mb-2">Eigener Betrag</label>
                        <div class="relative">
                            <input type="number" 
                                   id="custom-amount" 
                                   name="amount" 
                                   min="10" 
                                   max="1000" 
                                   step="1"
                                   value="{{ $suggestedAmount }}"
                                   class="block w-full pr-12 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                   placeholder="Betrag eingeben">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Mindestbetrag: 10 €, Maximalbetrag: 1.000 €</p>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Zahlungsmethode</label>
                        <div class="space-y-3">
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="credit_card" checked class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900">Kreditkarte</span>
                                    <span class="block text-sm text-gray-500">Visa, Mastercard, American Express</span>
                                </span>
                            </label>
                            <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="sepa" class="text-blue-600 focus:ring-blue-500">
                                <span class="ml-3">
                                    <span class="block text-sm font-medium text-gray-900">SEPA Lastschrift</span>
                                    <span class="block text-sm text-gray-500">Europäisches Bankkonto erforderlich</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between">
                        <a href="{{ route('business.billing.index') }}" class="text-gray-600 hover:text-gray-900">
                            Abbrechen
                        </a>
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                            <span id="amount-display">{{ $suggestedAmount }}</span> € aufladen
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Nach der Aufladung erhalten Sie eine Rechnung per E-Mail. Die Gutschrift erfolgt sofort nach Zahlungseingang.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountButtons = document.querySelectorAll('.amount-button');
    const customAmountInput = document.getElementById('custom-amount');
    const amountDisplay = document.getElementById('amount-display');
    
    // Handle preset amount buttons
    amountButtons.forEach(button => {
        button.addEventListener('click', function() {
            const amount = this.dataset.amount;
            customAmountInput.value = amount;
            amountDisplay.textContent = amount;
            
            // Update button styles
            amountButtons.forEach(btn => {
                btn.classList.remove('bg-blue-50', 'border-blue-500');
            });
            this.classList.add('bg-blue-50', 'border-blue-500');
        });
    });
    
    // Handle custom amount input
    customAmountInput.addEventListener('input', function() {
        amountDisplay.textContent = this.value || '0';
        
        // Remove selection from preset buttons
        amountButtons.forEach(btn => {
            btn.classList.remove('bg-blue-50', 'border-blue-500');
        });
    });
    
    // Handle form submission
    document.getElementById('topup-form').addEventListener('submit', function(e) {
        // Allow form submission to process through Stripe
        const amount = document.getElementById('custom-amount').value;
        if (!amount || amount < 10 || amount > 1000) {
            e.preventDefault();
            alert('Bitte geben Sie einen Betrag zwischen 10€ und 1000€ ein.');
        }
    });
});
</script>
@endsection