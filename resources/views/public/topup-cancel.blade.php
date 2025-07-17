<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zahlung abgebrochen - {{ $company->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Zahlung abgebrochen
                </h2>
                
                <p class="mt-2 text-center text-sm text-gray-600">
                    Die Aufladung wurde nicht abgeschlossen.
                </p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <p class="text-gray-900">
                    Das Guthaben von {{ $company->name }} wurde nicht aufgeladen.
                </p>
                
                <p class="mt-4 text-sm text-gray-500">
                    Falls Sie die Aufladung fortsetzen möchten, können Sie es erneut versuchen.
                </p>
            </div>
            
            <div>
                <a href="{{ route('public.topup.form', $company->id) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Erneut versuchen
                </a>
            </div>
        </div>
    </div>
</body>
</html>