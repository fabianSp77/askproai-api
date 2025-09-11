<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst(str_replace('-', ' ', $component)) }} - Flowbite Pro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <!-- Navigation Header -->
    <div class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ucfirst(str_replace('-', ' ', $component)) }}
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Category: {{ ucfirst(str_replace('-', ' ', $category)) }}
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="/flowbite-showcase" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        ← Back to Gallery
                    </a>
                    <a href="/flowbite" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        Component List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Component Display Area -->
    <div class="min-h-screen">
        @include($componentView)
    </div>

    <!-- Footer with Component Info -->
    <div class="bg-white border-t dark:bg-gray-800 dark:border-gray-700 mt-8">
        <div class="container mx-auto px-4 py-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Component Path</h3>
                    <code class="text-sm text-gray-600 dark:text-gray-400">{{ $componentView }}</code>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Usage</h3>
                    <code class="text-sm text-gray-600 dark:text-gray-400">
                        &lt;x-flowbite-pro.{{ $category }}.{{ $component }} /&gt;
                    </code>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Status</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Production Ready
                    </span>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>