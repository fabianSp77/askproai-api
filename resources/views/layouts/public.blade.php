<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'AskProAI') - AskProAI</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Simple Public Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <a href="/" class="text-xl font-semibold text-gray-800">
                                AskProAI
                            </a>
                        </div>
                    </div>

                    <!-- Right Navigation -->
                    <div class="flex items-center">
                        <a href="/admin" class="text-sm text-gray-700 hover:text-gray-900 mr-4">
                            Login
                        </a>
                        <a href="/hilfe" class="text-sm text-gray-700 hover:text-gray-900">
                            Hilfe
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} AskProAI. Alle Rechte vorbehalten.
                    <span class="mx-2">|</span>
                    <a href="/privacy" class="hover:text-gray-700">Datenschutz</a>
                    <span class="mx-2">|</span>
                    <a href="/cookie-policy" class="hover:text-gray-700">Cookie-Richtlinie</a>
                    <span class="mx-2">|</span>
                    <a href="/impressum" class="hover:text-gray-700">Impressum</a>
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>