<!DOCTYPE html>
<html>
<head>
    <title>Simple Livewire Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
</head>
<body>
    <h1>Simple Livewire Test</h1>
    
    <div>
        @if(auth()->check())
            <p>Logged in as: {{ auth()->user()->email }}</p>
        @else
            <p>Not logged in</p>
        @endif
    </div>

    @livewireScripts
    
    <script>
        // Test Livewire is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Livewire !== 'undefined') {
                console.log('Livewire is loaded');
                console.log('Livewire version:', Livewire.version);
            } else {
                console.error('Livewire is NOT loaded');
            }
        });
    </script>
</body>
</html>