<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire Integration Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Filament styles -->
    @filamentStyles
    @vite('resources/css/filament/admin/theme.css')
    
    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-center mb-8">Livewire Integration Test</h1>
        
        <!-- Test Component -->
        <livewire:test-counter />
        
        <!-- Debug Info -->
        <div class="mt-8 p-6 bg-white rounded-lg shadow-md max-w-2xl mx-auto">
            <h3 class="text-xl font-bold mb-4">Debug Information</h3>
            <ul class="space-y-2 text-sm">
                <li><strong>Laravel Version:</strong> {{ app()->version() }}</li>
                <li><strong>Livewire Version:</strong> {{ \Composer\InstalledVersions::getVersion('livewire/livewire') }}</li>
                <li><strong>Session Driver:</strong> {{ config('session.driver') }}</li>
                <li><strong>CSRF Token:</strong> <code class="bg-gray-100 px-2 py-1 rounded">{{ csrf_token() }}</code></li>
                <li><strong>Current Time:</strong> {{ now()->format('Y-m-d H:i:s') }}</li>
            </ul>
        </div>
        
        <!-- Console Check -->
        <div class="mt-4 p-4 bg-blue-100 rounded-lg max-w-2xl mx-auto">
            <p class="text-sm text-blue-800">
                <strong>Note:</strong> Check your browser console (F12) for any JavaScript errors.
            </p>
        </div>
    </div>
    
    <!-- Filament scripts -->
    @filamentScripts
    @vite('resources/js/filament/admin/app.js')
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Livewire Test Page Loaded');
            console.log('Livewire:', typeof window.Livewire !== 'undefined' ? 'Loaded' : 'Not Loaded');
            console.log('Alpine:', typeof window.Alpine !== 'undefined' ? 'Loaded' : 'Not Loaded');
            
            if (typeof window.Livewire !== 'undefined') {
                console.log('Livewire Version:', window.Livewire.version || 'Unknown');
            }
        });
    </script>
</body>
</html>