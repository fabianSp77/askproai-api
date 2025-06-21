@extends('layouts.public')

@section('title', 'Impressum')

@section('content')
<div class="bg-white">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Impressum</h1>
        
        <div class="prose prose-lg text-gray-600">
            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Angaben gemäß § 5 TMG</h2>
            
            <div class="bg-gray-50 p-6 rounded-lg">
                <p class="font-semibold text-gray-900">AskProAI GmbH</p>
                <p>Musterstraße 1<br>
                12345 Berlin<br>
                Deutschland</p>
            </div>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Vertreten durch</h2>
            <p>Geschäftsführer: Max Mustermann</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Kontakt</h2>
            <div class="bg-gray-50 p-6 rounded-lg">
                <p>Telefon: +49 (0) 30 12345678<br>
                Telefax: +49 (0) 30 12345679<br>
                E-Mail: info@askproai.de</p>
            </div>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Registereintrag</h2>
            <p>Eintragung im Handelsregister.<br>
            Registergericht: Amtsgericht Berlin-Charlottenburg<br>
            Registernummer: HRB 123456 B</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Umsatzsteuer-ID</h2>
            <p>Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:<br>
            DE123456789</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
            <div class="bg-gray-50 p-6 rounded-lg">
                <p>Max Mustermann<br>
                AskProAI GmbH<br>
                Musterstraße 1<br>
                12345 Berlin</p>
            </div>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">EU-Streitschlichtung</h2>
            <p>Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: 
            <a href="https://ec.europa.eu/consumers/odr/" class="text-blue-600 hover:underline" target="_blank">https://ec.europa.eu/consumers/odr/</a>.<br>
            Unsere E-Mail-Adresse finden Sie oben im Impressum.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Verbraucherstreitbeilegung/Universalschlichtungsstelle</h2>
            <p>Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Haftungsausschluss</h2>
            
            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Haftung für Inhalte</h3>
            <p>Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.</p>
            <p>Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden von entsprechenden Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Haftung für Links</h3>
            <p>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Urheberrecht</h3>
            <p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers.</p>

            <div class="mt-12 p-6 bg-yellow-50 rounded-lg">
                <p class="text-sm text-gray-700">
                    <strong>Wichtiger Hinweis:</strong> Dies ist ein Muster-Impressum. Bitte passen Sie alle Angaben an Ihre tatsächlichen Unternehmensdaten an und lassen Sie das Impressum von einem Rechtsanwalt prüfen, um sicherzustellen, dass es den gesetzlichen Anforderungen entspricht.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection