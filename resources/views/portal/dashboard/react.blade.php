<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @if(Auth::guard('portal')->check())
        <meta name="company-name" content="{{ Auth::guard('portal')->user()->company->name }}">
    @endif
    
    @if(session('is_admin_viewing'))
        <meta name="admin-viewing-company" content="{{ session('admin_viewing_company') }}">
    @endif

    <title>{{ config('app.name', 'AskProAI') }} - Dashboard</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/portal-dashboard.jsx'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>