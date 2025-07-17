@extends('portal.layouts.auth')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Business Portal Registrierung
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Registrieren Sie Ihr Unternehmen für das AskProAI Business Portal
            </p>
        </div>

        <form class="mt-8 space-y-6" action="{{ route('business.register.post') }}" method="POST">
            @csrf
            
            {{-- Honeypot Field (hidden) --}}
            <div style="position: absolute; left: -9999px;">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Es sind Fehler aufgetreten:
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                {{-- Firmendaten --}}
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Firmendaten</h3>
                    
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">
                            Firmenname <span class="text-red-500">*</span>
                        </label>
                        <input id="company_name" name="company_name" type="text" required 
                               value="{{ old('company_name') }}"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                    </div>

                    <div class="mt-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            Telefonnummer <span class="text-red-500">*</span>
                        </label>
                        <input id="phone" name="phone" type="tel" required 
                               value="{{ old('phone') }}"
                               placeholder="+49 123 456789"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                    </div>

                    <div class="mt-4">
                        <label for="address" class="block text-sm font-medium text-gray-700">
                            Adresse
                        </label>
                        <textarea id="address" name="address" rows="2"
                                  class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">{{ old('address') }}</textarea>
                    </div>
                </div>

                {{-- Benutzerdaten --}}
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Ihre Kontaktdaten</h3>
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Ihr Name <span class="text-red-500">*</span>
                        </label>
                        <input id="name" name="name" type="text" required 
                               value="{{ old('name') }}"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                    </div>

                    <div class="mt-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            E-Mail-Adresse <span class="text-red-500">*</span>
                        </label>
                        <input id="email" name="email" type="email" required 
                               value="{{ old('email') }}"
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                    </div>

                    <div class="mt-4">
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Passwort <span class="text-red-500">*</span>
                        </label>
                        <input id="password" name="password" type="password" required
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500">Mindestens 8 Zeichen</p>
                    </div>

                    <div class="mt-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Passwort bestätigen <span class="text-red-500">*</span>
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                    </div>
                </div>

                {{-- Nutzungsbedingungen --}}
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required
                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="font-medium text-gray-700">
                                Ich akzeptiere die <a href="#" class="text-indigo-600 hover:text-indigo-500">Nutzungsbedingungen</a> und <a href="#" class="text-indigo-600 hover:text-indigo-500">Datenschutzerklärung</a> <span class="text-red-500">*</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Hinweis --}}
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Nach der Registrierung wird Ihr Account von unserem Team überprüft und freigeschaltet. Sie erhalten eine E-Mail-Benachrichtigung, sobald Sie sich einloggen können.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Registrierung abschließen
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Bereits registriert? 
                    <a href="{{ route('business.login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Hier einloggen
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>
@endsection