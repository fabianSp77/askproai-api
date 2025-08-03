<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Business Portal') - {{ config('app.name', 'AskProAI') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    @if(Auth::guard('portal')->check())
        <nav class="bg-white shadow mb-8">
            <div class="container mx-auto px-6 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-4">
                        <a href="{{ route('business.dashboard') }}?nojs=1" class="text-gray-800 hover:text-blue-600">Dashboard</a>
                        <a href="{{ route('business.calls.index') }}" class="text-gray-800 hover:text-blue-600">Anrufe</a>
                        <a href="{{ route('business.appointments.index') }}" class="text-gray-800 hover:text-blue-600">Termine</a>
                        <a href="{{ route('business.customers.index') }}" class="text-gray-800 hover:text-blue-600">Kunden</a>
                        <a href="{{ route('business.settings.index') }}" class="text-gray-800 hover:text-blue-600">Einstellungen</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">{{ Auth::guard('portal')->user()->email }}</span>
                        <form method="POST" action="{{ route('business.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endif

    <main class="container mx-auto px-6">
        @yield('content')
    </main>
</body>
</html>