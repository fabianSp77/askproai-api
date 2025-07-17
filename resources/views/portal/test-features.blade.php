@extends('portal.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h1 class="text-3xl font-bold mb-4 flex items-center">
            <span class="text-4xl mr-3">🎉</span>
            Feature Test Dashboard
        </h1>
        <p class="text-gray-600">Testen Sie alle neuen Features direkt hier!</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow-lg">
            <div class="text-3xl mb-2">🎵</div>
            <h3 class="font-semibold">Audio Player</h3>
            <p class="text-sm opacity-90">In Call-Liste integriert</p>
        </div>
        <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-lg shadow-lg">
            <div class="text-3xl mb-2">📄</div>
            <h3 class="font-semibold">Transkripte</h3>
            <p class="text-sm opacity-90">Ein-/ausklappbar</p>
        </div>
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-lg shadow-lg">
            <div class="text-3xl mb-2">🌐</div>
            <h3 class="font-semibold">Übersetzung</h3>
            <p class="text-sm opacity-90">Multi-Language Support</p>
        </div>
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 rounded-lg shadow-lg">
            <div class="text-3xl mb-2">💳</div>
            <h3 class="font-semibold">Stripe</h3>
            <p class="text-sm opacity-90">Zahlungen integriert</p>
        </div>
    </div>

    <!-- Test Actions -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">🧪 Test-Aktionen</h2>
        
        <div class="grid md:grid-cols-2 gap-6">
            <div class="border rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <span class="text-2xl mr-2">📞</span>
                    Anruf-Features testen
                </h3>
                <p class="text-gray-600 mb-4">
                    Alle neuen Features sind in der Anrufliste integriert.
                </p>
                <a href="{{ route('business.calls.index') }}" class="inline-block bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition">
                    Zur Anrufliste →
                </a>
            </div>

            <div class="border rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <span class="text-2xl mr-2">💰</span>
                    Stripe-Integration testen
                </h3>
                <p class="text-gray-600 mb-4">
                    Testen Sie die Zahlungsabwicklung mit Stripe.
                </p>
                <a href="{{ route('business.billing.index') }}" class="inline-block bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition">
                    Zum Billing →
                </a>
            </div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-blue-900 mb-2">💡 Test-Anleitung:</h4>
            <ol class="list-decimal list-inside text-blue-800 space-y-2">
                <li>Gehen Sie zur <strong>Anrufliste</strong></li>
                <li>Klicken Sie auf die <strong>Action-Buttons</strong> bei jedem Anruf:
                    <ul class="list-disc list-inside ml-6 mt-1">
                        <li>▶️ Play-Button = Audio abspielen</li>
                        <li>💬 Message-Icon = Transkript anzeigen</li>
                        <li>🌐 Globe-Icon = Übersetzen</li>
                        <li>📋 Details = Neue Detail-Ansicht</li>
                    </ul>
                </li>
                <li>Für Stripe: Gehen Sie zu <strong>Billing</strong> → <strong>Guthaben aufladen</strong></li>
            </ol>
        </div>

        <div class="mt-6 p-4 bg-green-50 rounded-lg">
            <h4 class="font-semibold text-green-900 mb-2">✅ Verfügbare Test-Daten:</h4>
            <p class="text-green-800">
                Sie sehen echte Anrufdaten aus dem System. Alle Features sind voll funktionsfähig.
            </p>
        </div>
    </div>
</div>
@endsection