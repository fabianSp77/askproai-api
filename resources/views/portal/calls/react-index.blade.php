<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'AskProAI') }} - Business Portal</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @viteReactRefresh
    @vite(['resources/js/app-react-simple.jsx'])
</head>
<body class="font-sans antialiased">
    <div id="app" 
         data-auth="{{ json_encode(['user' => Auth::guard('portal')->user() ?: Auth::user()]) }}"
         data-api-url="{{ url('/api') }}"
         data-csrf="{{ csrf_token() }}"
         data-initial-route="/calls">
    </div>
    
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
        };
    </script>
</body>
</html>