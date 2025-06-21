@extends('layouts.public')

@section('title', 'Allgemeine Geschäftsbedingungen')

@section('content')
<div class="bg-white">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Allgemeine Geschäftsbedingungen (AGB)</h1>
        
        <div class="prose prose-lg text-gray-600">
            <p class="mb-6">Stand: {{ date('d.m.Y') }}</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 1 Geltungsbereich</h2>
            <p>(1) Diese Allgemeinen Geschäftsbedingungen (nachfolgend "AGB") gelten für alle Verträge, die zwischen der AskProAI GmbH (nachfolgend "Anbieter") und dem Kunden über die Plattform AskProAI geschlossen werden.</p>
            <p>(2) Verbraucher im Sinne dieser AGB ist jede natürliche Person, die ein Rechtsgeschäft zu Zwecken abschließt, die überwiegend weder ihrer gewerblichen noch ihrer selbständigen beruflichen Tätigkeit zugerechnet werden können.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 2 Leistungsbeschreibung</h2>
            <p>(1) AskProAI ist eine KI-gestützte Terminbuchungsplattform, die es Unternehmen ermöglicht, telefonische Anfragen automatisiert entgegenzunehmen und Termine zu vereinbaren.</p>
            <p>(2) Der genaue Leistungsumfang ergibt sich aus der jeweiligen Leistungsbeschreibung zum Zeitpunkt der Bestellung.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 3 Vertragsschluss</h2>
            <p>(1) Die Darstellung der Leistungen auf unserer Website stellt kein rechtlich bindendes Angebot, sondern eine Aufforderung zur Bestellung dar.</p>
            <p>(2) Durch Klicken des Buttons "Kostenpflichtig bestellen" geben Sie eine verbindliche Bestellung ab.</p>
            <p>(3) Der Anbieter bestätigt den Eingang der Bestellung unverzüglich per E-Mail. Diese Eingangsbestätigung stellt noch keine Annahme des Angebots dar.</p>
            <p>(4) Der Vertrag kommt erst durch die Bereitstellung der bestellten Leistung oder eine ausdrückliche Annahmeerklärung zustande.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 4 Preise und Zahlungsbedingungen</h2>
            <p>(1) Es gelten die zum Zeitpunkt der Bestellung angegebenen Preise. Alle Preise verstehen sich inklusive der gesetzlichen Mehrwertsteuer.</p>
            <p>(2) Die Zahlung erfolgt monatlich im Voraus per SEPA-Lastschrift, Kreditkarte oder Überweisung.</p>
            <p>(3) Bei Zahlungsverzug ist der Anbieter berechtigt, Verzugszinsen in Höhe von 5 Prozentpunkten über dem Basiszinssatz zu verlangen.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 5 Nutzungsrechte</h2>
            <p>(1) Der Kunde erhält ein nicht ausschließliches, nicht übertragbares Nutzungsrecht an der Software für die Dauer des Vertragsverhältnisses.</p>
            <p>(2) Eine Weitergabe der Zugangsdaten an Dritte ist untersagt.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 6 Verfügbarkeit</h2>
            <p>(1) Der Anbieter bemüht sich um eine Verfügbarkeit der Dienste von 99,5% im Jahresmittel.</p>
            <p>(2) Hiervon ausgenommen sind Ausfallzeiten durch Wartung und Software-Updates sowie Zeiten, in denen der Service aufgrund von technischen oder sonstigen Problemen, die nicht im Einflussbereich des Anbieters liegen, nicht erreichbar ist.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 7 Datenschutz</h2>
            <p>(1) Der Anbieter verarbeitet personenbezogene Daten des Kunden und dessen Endkunden ausschließlich im Rahmen der Datenschutzerklärung und der geltenden datenschutzrechtlichen Bestimmungen.</p>
            <p>(2) Der Kunde versichert, dass er zur Übermittlung der Daten seiner Endkunden berechtigt ist.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 8 Haftung</h2>
            <p>(1) Der Anbieter haftet unbeschränkt für Vorsatz und grobe Fahrlässigkeit sowie nach Maßgabe des Produkthaftungsgesetzes.</p>
            <p>(2) Bei leicht fahrlässiger Verletzung wesentlicher Vertragspflichten ist die Haftung auf den vertragstypischen, vorhersehbaren Schaden begrenzt.</p>
            <p>(3) Im Übrigen ist die Haftung ausgeschlossen.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 9 Vertragslaufzeit und Kündigung</h2>
            <p>(1) Der Vertrag wird auf unbestimmte Zeit geschlossen.</p>
            <p>(2) Der Vertrag kann von beiden Parteien mit einer Frist von einem Monat zum Monatsende gekündigt werden.</p>
            <p>(3) Das Recht zur außerordentlichen Kündigung aus wichtigem Grund bleibt unberührt.</p>
            <p>(4) Kündigungen bedürfen der Textform (z.B. E-Mail).</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 10 Änderungen der AGB</h2>
            <p>(1) Der Anbieter behält sich vor, diese AGB jederzeit zu ändern.</p>
            <p>(2) Änderungen werden dem Kunden mindestens sechs Wochen vor Inkrafttreten per E-Mail mitgeteilt.</p>
            <p>(3) Widerspricht der Kunde nicht innerhalb von sechs Wochen nach Zugang der Mitteilung, gelten die Änderungen als genehmigt.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">§ 11 Schlussbestimmungen</h2>
            <p>(1) Es gilt das Recht der Bundesrepublik Deutschland unter Ausschluss des UN-Kaufrechts.</p>
            <p>(2) Ist der Kunde Kaufmann, juristische Person des öffentlichen Rechts oder öffentlich-rechtliches Sondervermögen, ist ausschließlicher Gerichtsstand Berlin.</p>
            <p>(3) Sollten einzelne Bestimmungen dieser AGB unwirksam sein oder werden, bleibt die Wirksamkeit der übrigen Bestimmungen unberührt.</p>

            <div class="mt-12 p-6 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-700">
                    <strong>Hinweis:</strong> Diese AGB sind ein Muster und müssen an die spezifischen Gegebenheiten Ihres Unternehmens angepasst werden. Bitte lassen Sie diese von einem Rechtsanwalt prüfen.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection