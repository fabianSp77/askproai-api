<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaction {{ $transaction->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Transaction Details (Simple View)</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-blue-600">Transaktionsdetails</h2>
            
            <dl class="divide-y divide-gray-200">
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Transaktions-ID</dt>
                    <dd class="text-sm text-gray-900 col-span-2">{{ $transaction->id }}</dd>
                </div>
                
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Typ</dt>
                    <dd class="text-sm text-gray-900 col-span-2">
                        @php
                            $typeLabels = [
                                'topup' => 'Aufladung',
                                'usage' => 'Verbrauch',
                                'refund' => 'Erstattung',
                                'adjustment' => 'Anpassung',
                                'bonus' => 'Bonus',
                                'fee' => 'Gebühr',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs font-medium rounded-md 
                            @if($transaction->type == 'topup') bg-green-100 text-green-800
                            @elseif($transaction->type == 'usage') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $typeLabels[$transaction->type] ?? $transaction->type }}
                        </span>
                    </dd>
                </div>
                
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Betrag</dt>
                    <dd class="text-sm col-span-2">
                        <span class="font-bold {{ $transaction->amount_cents > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->amount_cents > 0 ? '+' : '' }}{{ number_format($transaction->amount_cents / 100, 2) }} €
                        </span>
                    </dd>
                </div>
                
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Saldo vorher</dt>
                    <dd class="text-sm text-gray-900 col-span-2">
                        {{ number_format($transaction->balance_before_cents / 100, 2) }} €
                    </dd>
                </div>
                
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Saldo nachher</dt>
                    <dd class="text-sm col-span-2">
                        <span class="font-semibold {{ $transaction->balance_after_cents < 0 ? 'text-red-600' : ($transaction->balance_after_cents < 1000 ? 'text-yellow-600' : 'text-green-600') }}">
                            {{ number_format($transaction->balance_after_cents / 100, 2) }} €
                        </span>
                    </dd>
                </div>
                
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Beschreibung</dt>
                    <dd class="text-sm text-gray-900 col-span-2">
                        {{ $transaction->description }}
                    </dd>
                </div>
                
                @if($transaction->tenant)
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Tenant</dt>
                    <dd class="text-sm text-gray-900 col-span-2">
                        {{ $transaction->tenant->name }}
                    </dd>
                </div>
                @endif
                
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm font-medium text-gray-500">Datum & Zeit</dt>
                    <dd class="text-sm text-gray-900 col-span-2">
                        {{ $transaction->created_at->format('d.m.Y H:i:s') }}
                        <span class="text-gray-500 text-xs ml-2">
                            ({{ $transaction->created_at->diffForHumans() }})
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
        
        <div class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <strong class="font-bold">✅ Success!</strong>
            <span class="block sm:inline">This simple view works! The data is being loaded correctly.</span>
        </div>
        
        <div class="mt-4">
            <a href="/admin/transactions/3" class="text-blue-600 hover:text-blue-800 underline">
                → Back to Admin Panel View (broken)
            </a>
        </div>
    </div>
</body>
</html>