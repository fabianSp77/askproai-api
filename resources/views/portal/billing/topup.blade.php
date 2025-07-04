@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Guthaben aufladen</h1>
            <p class="mt-1 text-sm text-gray-600">
                Wählen Sie einen Betrag aus oder geben Sie einen eigenen Betrag ein
            </p>
        </div>

        <!-- Current Balance Info -->
        @php
            $balanceStatus = app(\App\Services\BalanceMonitoringService::class)->getBalanceStatus($company);
            $currentBalance = $balanceStatus['effective_balance'] ?? 0;
            $minutesRemaining = $balanceStatus['available_minutes'] ?? 0;
        @endphp
        
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Ihr aktuelles Guthaben: <strong>{{ number_format($currentBalance, 2, ',', '.') }} €</strong>
                        @if($minutesRemaining > 0)
                            (ca. {{ number_format($minutesRemaining, 0, ',', '.') }} Minuten)
                        @endif
                    </p>
                </div>
            </div>
        </div>
        
        @if(session('is_admin_viewing'))
            <!-- Admin Warning -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Admin-Zugriff:</strong> Als Administrator können Sie keine Zahlungen für Kunden durchführen. 
                            Diese Seite dient nur zur Ansicht. Der Kunde muss sich selbst einloggen, um Guthaben aufzuladen.
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Top-up Form -->
        <form action="{{ route('business.billing.topup.process') }}" method="POST" id="topup-form">
            @csrf
            
            <!-- Suggested Amounts -->
            <div class="bg-white shadow sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Empfohlene Beträge
                    </h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach($suggestedAmounts as $amount)
                            <button type="button" 
                                    onclick="selectAmount({{ $amount }})"
                                    class="amount-button relative rounded-lg border border-gray-300 bg-white p-4 hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $selectedAmount == $amount ? 'border-indigo-500 ring-2 ring-indigo-500' : '' }}">
                                <div class="text-lg font-semibold text-gray-900">{{ number_format($amount, 0, ',', '.') }} €</div>
                                <div class="text-sm text-gray-500">
                                    ca. {{ number_format($amount / 0.42, 0, ',', '.') }} Min.
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Custom Amount -->
            <div class="bg-white shadow sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Oder eigenen Betrag eingeben
                    </h3>
                    <div class="mt-2 max-w-xs">
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                            <input type="number" 
                                   name="amount" 
                                   id="amount" 
                                   min="10" 
                                   max="10000" 
                                   step="0.01"
                                   value="{{ $selectedAmount }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
                                   placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">EUR</span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Mindestbetrag: 10 €, Maximalbetrag: 10.000 €
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Payment Info -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                <div class="flex items-center mb-4">
                    <svg class="h-6 w-6 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <h4 class="text-lg font-medium text-gray-900">Sichere Zahlung mit Stripe</h4>
                </div>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Ihre Zahlungsdaten werden sicher über Stripe verarbeitet
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Wir speichern keine Kreditkartendaten
                    </li>
                    <li class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        Das Guthaben wird sofort nach Zahlungseingang gutgeschrieben
                    </li>
                </ul>
            </div>
            
            <!-- Submit Button -->
            <div class="flex justify-end">
                <a href="{{ route('business.billing.index') }}" 
                   class="mr-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Abbrechen
                </a>
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Zur Zahlung
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function selectAmount(amount) {
        // Update input field
        document.getElementById('amount').value = amount;
        
        // Update button states
        document.querySelectorAll('.amount-button').forEach(button => {
            button.classList.remove('border-indigo-500', 'ring-2', 'ring-indigo-500');
        });
        
        // Find the clicked button and highlight it
        document.querySelectorAll('.amount-button').forEach(button => {
            const buttonAmount = button.querySelector('.text-lg').textContent.replace(/[^\d]/g, '');
            if (parseInt(buttonAmount) === amount) {
                button.classList.add('border-indigo-500', 'ring-2', 'ring-indigo-500');
            }
        });
    }
</script>
@endsection