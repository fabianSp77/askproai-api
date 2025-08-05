@extends('layouts.public')

@section('title', 'Hilfe-Center')

@section('content')
<div class="help-center-container">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl font-bold mb-4">AskProAI Hilfe-Center</h1>
            <p class="text-xl">Wie können wir Ihnen helfen?</p>
            
            <!-- Search Bar -->
            <div class="mt-8 max-w-2xl">
                <div class="relative">
                    <input type="text" 
                           placeholder="Suchen Sie nach Themen, z.B. 'Termin buchen', 'Passwort ändern'..." 
                           class="w-full px-6 py-4 rounded-lg text-gray-800 text-lg shadow-lg">
                    <button class="absolute right-4 top-4 text-gray-600 hover:text-gray-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gray-50 py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <a href="#" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition text-center">
                    <div class="text-blue-600 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg">Termin buchen</h3>
                    <p class="text-gray-600 mt-2">So buchen Sie Termine per Telefon</p>
                </a>

                <a href="#" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition text-center">
                    <div class="text-blue-600 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg">Termine verwalten</h3>
                    <p class="text-gray-600 mt-2">Termine ansehen und ändern</p>
                </a>

                <a href="#" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition text-center">
                    <div class="text-blue-600 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg">Mein Profil</h3>
                    <p class="text-gray-600 mt-2">Profildaten aktualisieren</p>
                </a>

                <a href="#" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition text-center">
                    <div class="text-blue-600 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg">Rechnungen</h3>
                    <p class="text-gray-600 mt-2">Rechnungen einsehen</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Categories -->
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Getting Started -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-100 rounded-full p-3 mr-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Erste Schritte</h2>
                </div>
                <ul class="space-y-3">
                    <li><a href="#" class="text-blue-600 hover:underline">Anmeldung und Registrierung</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Ihr erstes Telefonat</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Das Kundenportal verstehen</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Mobile App einrichten</a></li>
                </ul>
            </div>

            <!-- Appointments -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 rounded-full p-3 mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Termine verwalten</h2>
                </div>
                <ul class="space-y-3">
                    <li><a href="#" class="text-blue-600 hover:underline">Termine per Telefon buchen</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Termine im Portal verwalten</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Termine absagen oder verschieben</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Terminerinnerungen einstellen</a></li>
                </ul>
            </div>

            <!-- Troubleshooting -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 rounded-full p-3 mr-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Fehlerbehebung</h2>
                </div>
                <ul class="space-y-3">
                    <li><a href="#" class="text-blue-600 hover:underline">Häufige Probleme lösen</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Verbindungsprobleme</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Login-Probleme</a></li>
                    <li><a href="#" class="text-blue-600 hover:underline">Technische Anforderungen</a></li>
                </ul>
            </div>
        </div>

        <!-- Popular Articles -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6">Beliebte Artikel</h2>
            <div class="bg-white rounded-lg shadow">
                <div class="divide-y">
                    <a href="#" class="block p-4 hover:bg-gray-50 transition">
                        <h3 class="font-semibold text-lg mb-1">Wie buche ich einen Termin per Telefon?</h3>
                        <p class="text-gray-600">Schritt-für-Schritt Anleitung für die Terminbuchung über unseren KI-Assistenten...</p>
                    </a>
                    <a href="#" class="block p-4 hover:bg-gray-50 transition">
                        <h3 class="font-semibold text-lg mb-1">Passwort vergessen - was nun?</h3>
                        <p class="text-gray-600">So setzen Sie Ihr Passwort schnell und sicher zurück...</p>
                    </a>
                    <a href="#" class="block p-4 hover:bg-gray-50 transition">
                        <h3 class="font-semibold text-lg mb-1">Termine absagen oder verschieben</h3>
                        <p class="text-gray-600">Flexibel bleiben - so ändern Sie Ihre Termine ohne Gebühren...</p>
                    </a>
                    <a href="#" class="block p-4 hover:bg-gray-50 transition">
                        <h3 class="font-semibold text-lg mb-1">Rechnungen herunterladen</h3>
                        <p class="text-gray-600">Alle Rechnungen als PDF für Ihre Unterlagen speichern...</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="mt-12 bg-blue-50 rounded-lg p-8 text-center">
            <h2 class="text-2xl font-bold mb-4">Keine Antwort gefunden?</h2>
            <p class="text-lg text-gray-700 mb-6">Unser Support-Team hilft Ihnen gerne weiter!</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">
                <div class="bg-white rounded-lg p-6">
                    <div class="text-blue-600 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">Telefon</h3>
                    <p class="text-gray-600">+49 30 12345678</p>
                    <p class="text-sm text-gray-500">Mo-Fr 8:00-18:00</p>
                </div>

                <div class="bg-white rounded-lg p-6">
                    <div class="text-blue-600 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">E-Mail</h3>
                    <p class="text-gray-600">support@askproai.de</p>
                    <p class="text-sm text-gray-500">Antwort in 24h</p>
                </div>

                <div class="bg-white rounded-lg p-6">
                    <div class="text-blue-600 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-lg mb-2">Live-Chat</h3>
                    <p class="text-gray-600">Im Portal verfügbar</p>
                    <p class="text-sm text-gray-500">Mo-Fr 9:00-17:00</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.help-center-container {
    min-height: 100vh;
    background-color: #f9fafb;
}
</style>
@endsection