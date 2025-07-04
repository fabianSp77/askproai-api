@extends('portal.layouts.auth')

@section('title', 'Zwei-Faktor-Authentifizierung einrichten')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Zwei-Faktor-Authentifizierung
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Schützen Sie Ihr Konto mit zusätzlicher Sicherheit
            </p>
        </div>

        <div class="mt-8 bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            @if(session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ session('status') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('business.two-factor.enable') }}">
                @csrf

                <div class="space-y-6">
                    <!-- QR Code -->
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-4">
                            Scannen Sie diesen QR-Code mit Ihrer Authenticator-App:
                        </p>
                        <div class="inline-block p-4 bg-white border-2 border-gray-300 rounded-lg">
                            {!! $qrCode !!}
                        </div>
                    </div>

                    <!-- Manual Entry -->
                    <div>
                        <p class="text-sm text-gray-600 mb-2">
                            Oder geben Sie diesen Code manuell ein:
                        </p>
                        <div class="mt-1 p-3 bg-gray-100 rounded-md font-mono text-sm break-all">
                            {{ $secret }}
                        </div>
                    </div>

                    <!-- Verification Code -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">
                            Bestätigungscode
                        </label>
                        <div class="mt-1">
                            <input id="code" name="code" type="text" required autofocus
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('code') border-red-300 @enderror"
                                   placeholder="6-stelliger Code">
                            @error('code')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein
                        </p>
                    </div>

                    <!-- Recovery Codes Warning -->
                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Wichtiger Hinweis
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>
                                        Nach der Aktivierung erhalten Sie Wiederherstellungscodes. 
                                        Bewahren Sie diese sicher auf - sie sind Ihre einzige Möglichkeit, 
                                        auf Ihr Konto zuzugreifen, falls Sie Ihr Authenticator-Gerät verlieren.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-between">
                        <a href="{{ route('business.settings.security') }}" 
                           class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Zurück zu Einstellungen
                        </a>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            2FA aktivieren
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Help Text -->
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Empfohlene Authenticator-Apps:
                <a href="https://support.google.com/accounts/answer/1066447" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Google Authenticator
                </a>,
                <a href="https://www.microsoft.com/authenticator" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Microsoft Authenticator
                </a>
            </p>
        </div>
    </div>
</div>
@endsection