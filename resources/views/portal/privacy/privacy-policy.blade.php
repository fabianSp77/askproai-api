@extends('portal.layouts.app')

@section('title', __('Datenschutzerklärung'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Datenschutzerklärung') }}</h1>
    
    <div class="prose prose-lg max-w-none">
        <p class="text-gray-600">
            <strong>Stand: {{ now()->format('d.m.Y') }}</strong>
        </p>

        <h2>1. Verantwortlicher</h2>
        <p>
            Verantwortlich für die Datenverarbeitung auf dieser Website ist:
        </p>
        <address class="not-italic mb-6">
            {{ $company->name ?? 'AskProAI GmbH' }}<br>
            {{ $company->address ?? 'Beispielstraße 1' }}<br>
            {{ $company->postal_code ?? '10115' }} {{ $company->city ?? 'Berlin' }}<br>
            Deutschland<br>
            E-Mail: {{ $company->email ?? 'datenschutz@askproai.com' }}<br>
            Telefon: {{ $company->phone ?? '+49 30 12345678' }}
        </address>

        <h2>2. Datenschutzbeauftragter</h2>
        <p>
            Unseren Datenschutzbeauftragten erreichen Sie unter:
        </p>
        <address class="not-italic mb-6">
            {{ config('gdpr.data_protection_officer.name') }}<br>
            E-Mail: <a href="mailto:{{ config('gdpr.data_protection_officer.email') }}" class="text-blue-600 hover:text-blue-800">{{ config('gdpr.data_protection_officer.email') }}</a><br>
            Telefon: {{ config('gdpr.data_protection_officer.phone') }}<br>
            {{ config('gdpr.data_protection_officer.address') }}
        </address>

        <h2>3. Erhebung und Speicherung personenbezogener Daten</h2>
        
        <h3>3.1 Beim Besuch der Website</h3>
        <p>
            Bei jedem Zugriff auf unsere Website werden automatisch folgende Informationen erfasst:
        </p>
        <ul>
            <li>IP-Adresse des anfragenden Rechners</li>
            <li>Datum und Uhrzeit des Zugriffs</li>
            <li>Name und URL der abgerufenen Datei</li>
            <li>Website, von der aus der Zugriff erfolgt (Referrer-URL)</li>
            <li>Verwendeter Browser und Betriebssystem</li>
            <li>Name Ihres Internet-Service-Providers</li>
        </ul>
        <p>
            Diese Daten werden auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO erhoben, um die Funktionsfähigkeit 
            und Sicherheit unserer Website zu gewährleisten.
        </p>

        <h3>3.2 Bei der Terminbuchung</h3>
        <p>
            Wenn Sie über unser System einen Termin buchen, erheben wir folgende Daten:
        </p>
        <ul>
            <li>Vor- und Nachname</li>
            <li>Telefonnummer</li>
            <li>E-Mail-Adresse (optional)</li>
            <li>Geburtsdatum (optional)</li>
            <li>Gewünschter Termin und Service</li>
            <li>Notizen zum Termin (optional)</li>
        </ul>
        <p>
            Die Rechtsgrundlage für die Verarbeitung ist Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung).
        </p>

        <h3>3.3 Telefonanrufe über KI-System</h3>
        <p>
            Bei Anrufen über unser KI-gestütztes Telefonsystem werden folgende Daten verarbeitet:
        </p>
        <ul>
            <li>Anrufende Telefonnummer</li>
            <li>Datum und Uhrzeit des Anrufs</li>
            <li>Anrufdauer</li>
            <li>Transkript des Gesprächs</li>
            <li>Extrahierte Terminwünsche und Kundendaten</li>
        </ul>
        <p>
            Die Verarbeitung erfolgt auf Grundlage Ihrer Einwilligung (Art. 6 Abs. 1 lit. a DSGVO) 
            bzw. zur Vertragserfüllung (Art. 6 Abs. 1 lit. b DSGVO).
        </p>

        <h2>4. Weitergabe von Daten</h2>
        <p>
            Eine Übermittlung Ihrer persönlichen Daten an Dritte erfolgt nur in folgenden Fällen:
        </p>
        <ul>
            <li>Sie haben Ihre ausdrückliche Einwilligung dazu erteilt (Art. 6 Abs. 1 lit. a DSGVO)</li>
            <li>Die Weitergabe ist zur Vertragserfüllung erforderlich (Art. 6 Abs. 1 lit. b DSGVO)</li>
            <li>Es besteht eine gesetzliche Verpflichtung (Art. 6 Abs. 1 lit. c DSGVO)</li>
        </ul>

        <h3>4.1 Auftragsverarbeiter</h3>
        <p>Wir arbeiten mit folgenden Auftragsverarbeitern zusammen:</p>
        <ul>
            <li><strong>Retell.ai:</strong> KI-gestütztes Telefonsystem (Standort: EU, Auftragsverarbeitungsvertrag vorhanden)</li>
            <li><strong>Cal.com:</strong> Kalenderverwaltung (Standort: EU, DSGVO-konform)</li>
            <li><strong>Hosting-Provider:</strong> Server-Infrastruktur (Standort: Deutschland)</li>
        </ul>

        <h2>5. Speicherdauer</h2>
        <p>
            Wir speichern Ihre personenbezogenen Daten nur so lange, wie es für die jeweiligen Zwecke erforderlich ist:
        </p>
        <ul>
            <li><strong>Kundendaten:</strong> Für die Dauer der Geschäftsbeziehung plus gesetzliche Aufbewahrungsfristen</li>
            <li><strong>Terminbuchungen:</strong> {{ config('gdpr.retention_periods.appointments') }} Tage</li>
            <li><strong>Anrufaufzeichnungen:</strong> {{ config('gdpr.retention_periods.calls') }} Tage</li>
            <li><strong>Rechnungen:</strong> {{ config('gdpr.retention_periods.invoices') }} Tage (gesetzliche Aufbewahrungspflicht)</li>
            <li><strong>Cookies:</strong> Siehe unsere <a href="{{ route('portal.cookie-policy') }}" class="text-blue-600 hover:text-blue-800">Cookie-Richtlinie</a></li>
        </ul>

        <h2>6. Ihre Rechte</h2>
        <p>
            Sie haben folgende Rechte bezüglich Ihrer personenbezogenen Daten:
        </p>
        
        <h3>6.1 Auskunftsrecht (Art. 15 DSGVO)</h3>
        <p>
            Sie können Auskunft über Ihre von uns verarbeiteten personenbezogenen Daten verlangen.
        </p>

        <h3>6.2 Berichtigungsrecht (Art. 16 DSGVO)</h3>
        <p>
            Sie können die Berichtigung unrichtiger oder die Vervollständigung Ihrer bei uns gespeicherten Daten verlangen.
        </p>

        <h3>6.3 Löschungsrecht (Art. 17 DSGVO)</h3>
        <p>
            Sie können die Löschung Ihrer bei uns gespeicherten personenbezogenen Daten verlangen, soweit nicht die 
            Verarbeitung zur Ausübung des Rechts auf freie Meinungsäußerung, zur Erfüllung einer rechtlichen 
            Verpflichtung oder zur Geltendmachung von Rechtsansprüchen erforderlich ist.
        </p>

        <h3>6.4 Einschränkung der Verarbeitung (Art. 18 DSGVO)</h3>
        <p>
            Sie können die Einschränkung der Verarbeitung Ihrer personenbezogenen Daten verlangen.
        </p>

        <h3>6.5 Datenübertragbarkeit (Art. 20 DSGVO)</h3>
        <p>
            Sie haben das Recht, Ihre Daten in einem strukturierten, gängigen und maschinenlesbaren Format zu erhalten.
        </p>

        <h3>6.6 Widerspruchsrecht (Art. 21 DSGVO)</h3>
        <p>
            Sie können der Verarbeitung Ihrer personenbezogenen Daten jederzeit widersprechen.
        </p>

        <h3>6.7 Widerruf der Einwilligung</h3>
        <p>
            Sie können Ihre Einwilligung zur Datenverarbeitung jederzeit mit Wirkung für die Zukunft widerrufen.
        </p>

        <p class="bg-blue-50 p-4 rounded-lg">
            <strong>Zur Ausübung Ihrer Rechte nutzen Sie bitte unser <a href="{{ route('portal.privacy') }}" class="text-blue-600 hover:text-blue-800">Datenschutz-Center</a>.</strong>
        </p>

        <h2>7. Beschwerderecht</h2>
        <p>
            Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde über die Verarbeitung Ihrer 
            personenbezogenen Daten durch uns zu beschweren. Die für uns zuständige Aufsichtsbehörde ist:
        </p>
        <address class="not-italic">
            Berliner Beauftragte für Datenschutz und Informationsfreiheit<br>
            Friedrichstr. 219<br>
            10969 Berlin<br>
            Telefon: +49 30 13889-0<br>
            E-Mail: mailbox@datenschutz-berlin.de
        </address>

        <h2>8. Datensicherheit</h2>
        <p>
            Wir verwenden innerhalb des Website-Besuchs das verbreitete SSL-Verfahren (Secure Socket Layer) in 
            Verbindung mit der jeweils höchsten Verschlüsselungsstufe, die von Ihrem Browser unterstützt wird. 
            Zusätzlich setzen wir technische und organisatorische Sicherheitsmaßnahmen ein, um Ihre Daten gegen 
            zufällige oder vorsätzliche Manipulationen, teilweisen oder vollständigen Verlust, Zerstörung oder 
            gegen den unbefugten Zugriff Dritter zu schützen.
        </p>

        <h2>9. Aktualität und Änderung dieser Datenschutzerklärung</h2>
        <p>
            Diese Datenschutzerklärung ist aktuell gültig und hat den Stand {{ now()->format('F Y') }}. 
            Durch die Weiterentwicklung unserer Website und Angebote oder aufgrund geänderter gesetzlicher 
            beziehungsweise behördlicher Vorgaben kann es notwendig werden, diese Datenschutzerklärung zu ändern.
        </p>
    </div>
</div>
@endsection