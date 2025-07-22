<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kundenportal') - {{ config('app.name', 'AskProAI') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    @if(file_exists(public_path('build/manifest.json')))
        <link rel="stylesheet" href="{{ asset('build/assets/app-CAAkOUKa.css') }}">
    @else
        <style>
            /* Fallback styles */
            .min-h-screen { min-height: 100vh; }
            .flex { display: flex; }
            .items-center { align-items: center; }
            .justify-center { justify-content: center; }
            .bg-gray-50 { background-color: #f9fafb; }
            .bg-gray-100 { background-color: #f3f4f6; }
            .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
            .px-4 { padding-left: 1rem; padding-right: 1rem; }
            .max-w-md { max-width: 28rem; }
            .w-full { width: 100%; }
            .space-y-8 > * + * { margin-top: 2rem; }
            .text-center { text-align: center; }
            .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
            .font-extrabold { font-weight: 800; }
            .text-gray-900 { color: #111827; }
            .text-gray-600 { color: #4b5563; }
            .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
            .rounded-md { border-radius: 0.375rem; }
            .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
            .border { border-width: 1px; }
            .border-gray-300 { border-color: #d1d5db; }
            .block { display: block; }
            .relative { position: relative; }
            .appearance-none { appearance: none; }
            .px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
            .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
            .focus\:outline-none:focus { outline: 2px solid transparent; outline-offset: 2px; }
            .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0; }
        </style>
    @endif
    
    <style>
        :root {
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --primary-300: #93bbfc;
            --primary-400: #60a5fa;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-800: #1e40af;
            --primary-900: #1e3a8a;
        }
        
        .text-primary-600 { color: var(--primary-600); }
        .text-primary-500 { color: var(--primary-500); }
        .bg-primary-600 { background-color: var(--primary-600); }
        .bg-primary-700 { background-color: var(--primary-700); }
        .hover\:text-primary-500:hover { color: var(--primary-500); }
        .hover\:bg-primary-700:hover { background-color: var(--primary-700); }
        .focus\:ring-primary-500:focus { --tw-ring-color: var(--primary-500); }
        .focus\:border-primary-500:focus { border-color: var(--primary-500); }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @yield('content')
    </div>
</body>
</html>