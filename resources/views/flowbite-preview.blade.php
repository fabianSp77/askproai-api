<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Component Preview</title>
    
    <!-- Flowbite CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            background: #f9fafb;
            padding: 2rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .preview-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 1200px;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        @if(\Illuminate\Support\Facades\View::exists('components.' . $componentName))
            <x-dynamic-component :component="$componentName" />
        @else
            <div class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="text-gray-500">Component not found: {{ $componentName }}</p>
            </div>
        @endif
    </div>
    
    <!-- Alpine.js (Load before Flowbite for proper initialization) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Flowbite JS -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    
    <!-- Enhanced Preview Initialization -->
    <script src="/js/flowbite-preview-init.js"></script>
    
    <script>
        // Additional debugging and fallback
        window.addEventListener('flowbite-preview-ready', function(e) {
            console.log('✅ Preview components ready:', e.detail);
        });
        
        // Manual reinit helper for testing
        window.forceReinit = function() {
            console.log('Force re-initializing all components...');
            if (window.reinitPreview) window.reinitPreview();
            if (typeof initFlowbite !== 'undefined') initFlowbite();
            if (typeof Alpine !== 'undefined' && !Alpine.version) Alpine.start();
        };
    </script>
</body>
</html>