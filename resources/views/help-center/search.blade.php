@extends('layouts.public')

@section('title', 'Hilfe-Suche')

@section('content')
<div class="help-search-container">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-8">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-4">Suchergebnisse</h1>
            
            <!-- Search Bar -->
            <form action="{{ route('help.search') }}" method="GET" class="max-w-2xl">
                <div class="relative">
                    <input type="text" 
                           name="q"
                           value="{{ $query }}"
                           placeholder="Suchen Sie nach Themen..." 
                           class="w-full px-6 py-4 rounded-lg text-gray-800 text-lg shadow-lg">
                    <button type="submit" class="absolute right-4 top-4 text-gray-600 hover:text-gray-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            @if($query)
                <p class="text-gray-600 mb-6">
                    {{ count($results) }} Ergebnis{{ count($results) !== 1 ? 'se' : '' }} für "<strong>{{ $query }}</strong>"
                </p>

                @if(count($results) > 0)
                    <div class="space-y-4">
                        @foreach($results as $result)
                            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                                <a href="{{ $result['url'] }}" class="block">
                                    <h2 class="text-xl font-semibold text-blue-600 mb-2">{{ $result['title'] }}</h2>
                                    <p class="text-gray-600 mb-2">{{ $result['excerpt'] }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ ucfirst(str_replace('-', ' ', $result['category'])) }} › {{ $result['title'] }}
                                    </p>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-yellow-800 mb-2">Keine Ergebnisse gefunden</h3>
                        <p class="text-yellow-700 mb-4">
                            Leider konnten wir keine Artikel finden, die zu Ihrer Suche passen.
                        </p>
                        <div class="space-y-2">
                            <p class="text-sm text-yellow-700">Tipps für bessere Suchergebnisse:</p>
                            <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                                <li>Überprüfen Sie die Rechtschreibung</li>
                                <li>Verwenden Sie allgemeinere Begriffe</li>
                                <li>Probieren Sie andere Schlüsselwörter</li>
                            </ul>
                        </div>
                    </div>
                @endif
            @else
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <p class="text-blue-700">
                        Geben Sie einen Suchbegriff ein, um die Hilfe zu durchsuchen.
                    </p>
                </div>
            @endif

            <!-- Popular Topics -->
            <div class="mt-12">
                <h3 class="text-xl font-semibold mb-6">Beliebte Themen</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'first-call']) }}" 
                       class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                        <h4 class="font-medium text-blue-600">Ihr erstes Telefonat</h4>
                        <p class="text-sm text-gray-600 mt-1">So funktioniert die Terminbuchung per Telefon</p>
                    </a>
                    <a href="{{ route('help.article', ['category' => 'appointments', 'topic' => 'manage-appointments']) }}" 
                       class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                        <h4 class="font-medium text-blue-600">Termine verwalten</h4>
                        <p class="text-sm text-gray-600 mt-1">Termine im Portal ansehen und bearbeiten</p>
                    </a>
                    <a href="{{ route('help.article', ['category' => 'troubleshooting', 'topic' => 'common-issues']) }}" 
                       class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                        <h4 class="font-medium text-blue-600">Häufige Probleme lösen</h4>
                        <p class="text-sm text-gray-600 mt-1">Schnelle Hilfe bei typischen Herausforderungen</p>
                    </a>
                    <a href="{{ route('help.article', ['category' => 'billing', 'topic' => 'view-invoices']) }}" 
                       class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                        <h4 class="font-medium text-blue-600">Rechnungen einsehen</h4>
                        <p class="text-sm text-gray-600 mt-1">Alle Ihre Rechnungen an einem Ort</p>
                    </a>
                </div>
            </div>

            <!-- Still need help? -->
            <div class="mt-12 bg-gray-50 rounded-lg p-8 text-center">
                <h3 class="text-xl font-semibold mb-4">Benötigen Sie weitere Hilfe?</h3>
                <p class="text-gray-600 mb-6">Unser Support-Team ist für Sie da!</p>
                <div class="flex justify-center space-x-4">
                    <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                        💬 Live-Chat starten
                    </a>
                    <a href="tel:+493012345678" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition">
                        📞 Anrufen
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection