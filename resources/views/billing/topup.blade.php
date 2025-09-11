@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Top Up Your Balance</h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Current Balance</h2>
            <p class="text-3xl font-bold text-gray-900">
                {{ number_format(auth()->user()->tenant->balance_cents / 100, 2) }} €
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-6">Select Amount to Add</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                @foreach([25, 50, 100, 250, 500, 1000] as $amount)
                <button onclick="selectAmount({{ $amount }})" 
                        class="amount-btn p-4 border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition text-center">
                    <span class="text-2xl font-bold">{{ $amount }} €</span>
                    @if($amount == 50)
                        <span class="text-xs text-green-600 block mt-1">Most Popular</span>
                    @endif
                </button>
                @endforeach
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Amount (€)</label>
                <input type="number" id="customAmount" min="10" max="5000" step="5" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Enter custom amount">
            </div>

            <form action="{{ route('billing.checkout') }}" method="GET" id="topupForm">
                <input type="hidden" name="amount" id="selectedAmount" value="50">
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600">Amount to add:</span>
                        <span class="font-bold text-lg" id="displayAmount">50.00 €</span>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-500">
                        <span>Payment method:</span>
                        <span>Credit/Debit Card (Stripe)</span>
                    </div>
                </div>

                <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                    Proceed to Secure Checkout
                </button>
                
                <p class="text-xs text-gray-500 text-center mt-4">
                    <svg class="inline w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                    </svg>
                    Secure payment processed by Stripe
                </p>
            </form>
        </div>

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">Why add balance?</h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li>• Uninterrupted service for your API calls</li>
                <li>• Automatic usage tracking and billing</li>
                <li>• Real-time balance updates</li>
                <li>• Detailed transaction history</li>
            </ul>
        </div>
    </div>
</div>

<script>
function selectAmount(amount) {
    // Update UI
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.classList.remove('border-blue-500', 'bg-blue-50');
        btn.classList.add('border-gray-300');
    });
    event.target.closest('.amount-btn').classList.remove('border-gray-300');
    event.target.closest('.amount-btn').classList.add('border-blue-500', 'bg-blue-50');
    
    // Update form
    document.getElementById('selectedAmount').value = amount;
    document.getElementById('displayAmount').textContent = amount.toFixed(2) + ' €';
    document.getElementById('customAmount').value = '';
}

document.getElementById('customAmount').addEventListener('input', function(e) {
    const amount = parseFloat(e.target.value);
    if (amount >= 10 && amount <= 5000) {
        document.getElementById('selectedAmount').value = amount;
        document.getElementById('displayAmount').textContent = amount.toFixed(2) + ' €';
        
        // Deselect preset buttons
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'bg-blue-50');
            btn.classList.add('border-gray-300');
        });
    }
});
</script>
@endsection