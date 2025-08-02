<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Business Portal Dashboard - {{ config('app.name', 'AskProAI') }}</title>
    
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    @endif
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-gray-100">
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h1 class="text-2xl font-semibold text-gray-900 mb-4">
                    Willkommen im Business Portal, {{ $user->name }}!
                </h1>
                
                <p class="text-gray-600">
                    Sie sind erfolgreich eingeloggt. Das Dashboard wird geladen...
                </p>
                
                <div class="mt-6">
                    <p class="text-sm text-gray-500">
                        Firma: {{ $company->name ?? 'N/A' }}<br>
                        E-Mail: {{ $user->email }}
                    </p>
                </div>
                
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-blue-900">Anrufe</h3>
                        <p class="mt-2 text-3xl font-bold text-blue-600">-</p>
                        <p class="text-sm text-blue-700 mt-1">Wird geladen...</p>
                    </div>
                    
                    <div class="bg-green-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-green-900">Termine</h3>
                        <p class="mt-2 text-3xl font-bold text-green-600">-</p>
                        <p class="text-sm text-green-700 mt-1">Wird geladen...</p>
                    </div>
                    
                    <div class="bg-purple-50 p-6 rounded-lg">
                        <h3 class="text-lg font-medium text-purple-900">Guthaben</h3>
                        <p class="mt-2 text-3xl font-bold text-purple-600">-</p>
                        <p class="text-sm text-purple-700 mt-1">Wird geladen...</p>
                    </div>
                </div>
                
                <div class="mt-8">
                    <form method="POST" action="/business/logout" style="display: inline;">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Abmelden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>