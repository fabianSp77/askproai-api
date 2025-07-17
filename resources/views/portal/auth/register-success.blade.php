@extends('portal.layouts.auth')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center">
                    {{-- Success Icon --}}
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <h3 class="mt-4 text-lg leading-6 font-medium text-gray-900">
                        Registrierung erfolgreich eingereicht!
                    </h3>

                    <div class="mt-4 text-sm text-gray-600">
                        <p>Vielen Dank für Ihre Registrierung bei AskProAI.</p>
                        <p class="mt-2">Wir haben Ihre Anfrage erhalten und werden diese schnellstmöglich bearbeiten.</p>
                    </div>

                    <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4 text-left">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-blue-800">Was passiert als Nächstes?</h4>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li>Unser Team überprüft Ihre Registrierung</li>
                                        <li>Sie erhalten eine E-Mail-Bestätigung an die angegebene Adresse</li>
                                        <li>Nach der Freischaltung können Sie sich mit Ihren Zugangsdaten einloggen</li>
                                        <li>Die Bearbeitung dauert in der Regel 1-2 Werktage</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm text-gray-600">
                            Bei Fragen erreichen Sie uns unter:
                        </p>
                        <p class="mt-1">
                            <a href="mailto:support@askproai.de" class="text-indigo-600 hover:text-indigo-500">
                                support@askproai.de
                            </a>
                        </p>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('business.login') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Zur Login-Seite
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection