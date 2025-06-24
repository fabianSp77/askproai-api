<!DOCTYPE html>
<html>
<head>
    <title>Livewire Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
</head>
<body>
    <h1>Livewire Test Page</h1>
    
    <div>
        <p>Session ID: {{ session()->getId() }}</p>
        <p>User: {{ auth()->check() ? auth()->user()->email : 'Not logged in' }}</p>
        <p>CSRF Token: {{ csrf_token() }}</p>
    </div>
    
    <hr>
    
    <livewire:test-component />
    
    @livewireScripts
</body>
</html>