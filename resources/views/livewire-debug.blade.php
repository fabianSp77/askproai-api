<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire Debug</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
</head>
<body>
    <div style="padding: 20px;">
        <h1>Livewire Debug Information</h1>
        
        <h2>Configuration</h2>
        <pre>
App URL: {{ config('app.url') }}
Current URL: {{ request()->url() }}
Current Host: {{ request()->getHost() }}
Livewire Asset URL: {{ config('livewire.asset_url') }}
Livewire App URL: {{ config('livewire.app_url') }}
CSRF Token: {{ csrf_token() }}
        </pre>
        
        <h2>Livewire Routes</h2>
        <pre>
@php
    $routes = collect(\Route::getRoutes())->filter(function ($route) {
        return str_contains($route->uri(), 'livewire');
    });
@endphp
@foreach($routes as $route)
{{ implode('|', $route->methods()) }} {{ $route->uri() }}
@endforeach
        </pre>
        
        <h2>JavaScript Configuration</h2>
        <div id="js-debug"></div>
    </div>
    
    @livewireScripts
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const debugDiv = document.getElementById('js-debug');
            
            if (typeof window.Livewire !== 'undefined') {
                debugDiv.innerHTML = '<pre>' + 
                    'Livewire is loaded\n' +
                    'Update endpoint: ' + (window.Livewire.connection?.updateUri || 'Not set') + '\n' +
                    'CSRF Token: ' + (document.querySelector('meta[name="csrf-token"]')?.content || 'Not found') +
                    '</pre>';
            } else {
                debugDiv.innerHTML = '<pre style="color: red;">Livewire is NOT loaded!</pre>';
            }
        });
    </script>
</body>
</html>