<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guthaben aufladen - {{ $company->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Guthaben aufladen
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    für {{ $company->name }}
                </p>
            </div>
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form class="mt-8 space-y-6" action="{{ route('public.topup.process', $company->id) }}" method="POST">
                @csrf
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Ihr Name
                        </label>
                        <input id="name" name="name" type="text" required 
                               class="appearance-none rounded-t-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Max Mustermann">
                    </div>
                    
                    <div class="mt-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            E-Mail-Adresse
                        </label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="max@beispiel.de">
                    </div>
                    
                    <div class="mt-4">
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                            Betrag (EUR)
                        </label>
                        <input id="amount" name="amount" type="number" step="0.01" min="10" max="10000" required 
                               value="{{ $presetAmount ?? '' }}"
                               class="appearance-none rounded-b-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="100.00">
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-2">Vorgeschlagene Beträge:</p>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach($suggestedAmounts as $suggestedAmount)
                            <button type="button" 
                                    onclick="document.getElementById('amount').value = {{ $suggestedAmount }}"
                                    class="px-3 py-2 text-sm bg-gray-200 hover:bg-gray-300 rounded-md transition">
                                {{ number_format($suggestedAmount, 0) }}€
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Zur Zahlung
                    </button>
                </div>
                
                <div class="text-xs text-gray-500 text-center">
                    <p>Sie werden zu Stripe weitergeleitet, um die Zahlung sicher abzuschließen.</p>
                    <p class="mt-1">Akzeptierte Zahlungsmethoden: Kreditkarte</p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>