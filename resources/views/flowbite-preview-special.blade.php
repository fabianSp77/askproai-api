<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Component Preview - Special Component</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body>
    <div class="max-w-2xl mx-auto p-8">
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            @if($reason === 'dashboard')
                <div class="mx-auto mb-6 w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Dashboard Component</h2>
                <p class="text-lg text-gray-600 mb-6">
                    This is a complete dashboard page with complex interactive elements that cannot be rendered in isolation.
                </p>
                <div class="bg-purple-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-purple-800">
                        <strong>Component:</strong> {{ str_replace('flowbite.content.', '', $componentName) }}
                    </p>
                </div>
                <div class="text-sm text-gray-500">
                    Dashboard components include charts, data tables, and other elements that require a full application context to function properly.
                </div>
            @elseif($reason === 'stub')
                <div class="mx-auto mb-6 w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Incomplete Component</h2>
                <p class="text-lg text-gray-600 mb-6">
                    This component appears to be a placeholder or stub file with no implementation.
                </p>
                <div class="bg-yellow-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-yellow-800">
                        <strong>Component:</strong> {{ str_replace('flowbite.', '', $componentName) }}
                    </p>
                    <p class="text-sm text-yellow-800 mt-1">
                        <strong>File Size:</strong> {{ $size }} (nearly empty)
                    </p>
                </div>
                <div class="text-sm text-gray-500">
                    This component needs to be implemented with actual content before it can be used.
                </div>
            @elseif($reason === 'large')
                <div class="mx-auto mb-6 w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Large Component</h2>
                <p class="text-lg text-gray-600 mb-6">
                    This component is too large ({{ $size }}) to be rendered safely in preview mode.
                </p>
                <div class="bg-amber-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-amber-800">
                        <strong>Component:</strong> {{ str_replace('flowbite.', '', $componentName) }}
                    </p>
                    <p class="text-sm text-amber-800 mt-1">
                        <strong>File Size:</strong> {{ $size }}
                    </p>
                </div>
                <div class="text-sm text-gray-500">
                    Large components typically contain complete page layouts or complex functionality that requires specific data and context.
                </div>
            @endif
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-gray-400">
                    To view this component, integrate it directly into your application with the required dependencies and data.
                </p>
            </div>
        </div>
    </div>
</body>
</html>