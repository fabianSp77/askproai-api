<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zahlung erfolgreich - {{ $company->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Zahlung erfolgreich!
                </h2>
                
                <p class="mt-2 text-center text-sm text-gray-600">
                    Vielen Dank{{ $customerName ? ', ' . $customerName : '' }}!
                </p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <p class="text-lg font-medium text-gray-900">
                    Das Guthaben von {{ $company->name }} wurde erfolgreich aufgeladen.
                </p>
                
                @if($amount)
                    <p class="mt-2 text-gray-600">
                        Aufgeladener Betrag: <span class="font-bold">{{ number_format($amount, 2, ',', '.') }}€</span>
                    </p>
                @endif
                
                <p class="mt-4 text-sm text-gray-500">
                    Sie erhalten in Kürze eine Bestätigung per E-Mail.
                </p>
            </div>
            
            <div class="text-center">
                <p class="text-sm text-gray-500">
                    Sie können dieses Fenster jetzt schließen.
                </p>
            </div>
        </div>
    </div>
</body>
</html>