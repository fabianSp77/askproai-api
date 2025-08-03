<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <meta name="user" content="{{ json_encode([
        'id' => auth()->guard('portal')->user()->id,
        'name' => auth()->guard('portal')->user()->name,
        'email' => auth()->guard('portal')->user()->email,
        'role' => auth()->guard('portal')->user()->role ?? 'user'
    ]) }}">

    <title>{{ config('app.name', 'AskProAI') }} - Business Portal</title>
    
    @vite(['resources/css/app.css'])
    @vite(['resources/js/bundles/portal.jsx'])
</head>
<body>
    <div id="app" class="min-h-screen">
        <!-- React app will mount here -->
        <div class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Lade Dashboard...</p>
            </div>
        </div>
    </div>
</body>
</html>