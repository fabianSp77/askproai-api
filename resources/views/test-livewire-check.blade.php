<!DOCTYPE html>
<html>
<head>
    <title>Livewire Check</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
    <script src="{{ asset('vendor/livewire/livewire.js') }}"></script>
    <script src="{{ asset('js/filament/filament/app.js') }}"></script>
</head>
<body>
    <div style="padding: 20px;">
        <h1>Livewire Status Check</h1>
        
        <div id="status"></div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let status = document.getElementById('status');
                let html = '<h2>Livewire Check Results:</h2><ul>';
                
                // Check if Livewire exists
                if (typeof window.Livewire !== 'undefined') {
                    html += '<li style="color: green;">✓ Livewire is loaded</li>';
                    html += '<li>Livewire version: ' + (window.Livewire.version || 'Unknown') + '</li>';
                    html += '<li>Is initialized: ' + (window.Livewire.isInitialized ? 'Yes' : 'No') + '</li>';
                } else {
                    html += '<li style="color: red;">✗ Livewire is NOT loaded</li>';
                }
                
                // Check Alpine
                if (typeof window.Alpine !== 'undefined') {
                    html += '<li style="color: green;">✓ Alpine.js is loaded</li>';
                } else {
                    html += '<li style="color: red;">✗ Alpine.js is NOT loaded</li>';
                }
                
                // Check wire:navigate
                if (document.querySelector('[wire\\:navigate]')) {
                    html += '<li style="color: green;">✓ wire:navigate found</li>';
                } else {
                    html += '<li style="color: orange;">⚠ No wire:navigate elements found</li>';
                }
                
                // Check for Livewire scripts
                let livewireScripts = Array.from(document.scripts).filter(s => s.src && s.src.includes('livewire'));
                html += '<li>Livewire scripts found: ' + livewireScripts.length + '</li>';
                livewireScripts.forEach(s => {
                    html += '<li style="margin-left: 20px;">- ' + s.src + '</li>';
                });
                
                html += '</ul>';
                
                // Check console errors
                html += '<h3>Check Console for Errors</h3>';
                html += '<p>Press F12 to open Developer Tools and check the Console tab.</p>';
                
                status.innerHTML = html;
            });
        </script>
    </div>
</body>
</html>