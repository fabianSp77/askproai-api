<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaction Test</title>
    @livewireStyles
    @filamentStyles
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-6">Transaction Test View (ID: {{ $id }})</h1>
        
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <p class="text-green-600 font-bold">This is a test page to verify Livewire component works.</p>
        </div>
        
        @livewire('transaction-viewer', ['id' => $id])
    </div>
    
    @livewireScripts
    @filamentScripts
</body>
</html>