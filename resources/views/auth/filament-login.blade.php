<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Anmelden - {{ config('app.name', 'AskProAI Admin') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        .fi-body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="fi-body min-h-screen">
    <div class="flex min-h-screen flex-col items-center justify-center">
        <div class="fi-simple-main mx-auto w-full max-w-lg px-6">
            <div class="fi-simple-page">
                <section class="grid auto-cols-fr gap-y-6">
                    <div class="fi-simple-header">
                        <h1 class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                            AskProAI Admin
                        </h1>
                        <p class="fi-simple-header-subheading text-center text-sm text-gray-500 dark:text-gray-400">
                            Melden Sie sich an
                        </p>
                    </div>

                    <div class="fi-simple-main-ctn">
                        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <div class="fi-section-content-ctn p-6">
                                <form method="POST" action="{{ route('direct.login.submit') }}" class="space-y-6">
                                    @csrf
                                    
                                    @if ($errors->any())
                                        <div class="rounded-lg bg-danger-50 p-4 text-sm text-danger-800 dark:bg-danger-800/10 dark:text-danger-400">
                                            @foreach ($errors->all() as $error)
                                                <p>{{ $error }}</p>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="space-y-2">
                                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                            E-Mail-Adresse
                                        </label>
                                        <input
                                            type="email"
                                            name="email"
                                            id="email"
                                            value="{{ old('email') }}"
                                            required
                                            autofocus
                                            autocomplete="email"
                                            class="block w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="admin@askproai.de"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Passwort
                                        </label>
                                        <input
                                            type="password"
                                            name="password"
                                            id="password"
                                            required
                                            autocomplete="current-password"
                                            class="block w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="••••••••"
                                        />
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="remember"
                                            id="remember"
                                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                        <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-200">
                                            Angemeldet bleiben
                                        </label>
                                    </div>

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-75 hover:bg-primary-500 focus:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus:bg-primary-400 dark:focus:ring-offset-gray-800"
                                    >
                                        Anmelden
                                    </button>

                                    @if(url('/admin/login-fix'))
                                        <div class="text-center">
                                            <a href="/admin/login-fix" class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                                Alternative Anmeldung verwenden
                                            </a>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    @livewireScripts
</body>
</html>