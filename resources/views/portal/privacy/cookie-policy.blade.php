@extends('portal.layouts.app')

@section('title', __('Cookie-Richtlinie'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Cookie-Richtlinie') }}</h1>
    
    <div class="prose prose-lg max-w-none">
        <p class="text-gray-600">
            <strong>Stand: {{ now()->format('d.m.Y') }}</strong>
        </p>

        <h2>1. Was sind Cookies?</h2>
        <p>
            Cookies sind kleine Textdateien, die auf Ihrem Gerät gespeichert werden, wenn Sie unsere Website besuchen. 
            Sie helfen uns dabei, Ihre Präferenzen zu speichern, die Website-Funktionalität zu verbessern und zu verstehen, 
            wie Nutzer unsere Website verwenden.
        </p>

        <h2>2. Welche Arten von Cookies verwenden wir?</h2>
        
        @foreach($cookieCategories as $key => $category)
        <div class="bg-gray-50 rounded-lg p-6 mb-6">
            <h3 class="text-xl font-semibold mb-3">{{ $category['name'] }}</h3>
            <p class="mb-4">{{ $category['description'] }}</p>
            
            @if(isset($category['cookies']) && count($category['cookies']) > 0)
            <h4 class="font-medium mb-2">Verwendete Cookies:</h4>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="text-left text-sm font-medium text-gray-900 pb-2">Cookie-Name</th>
                        <th class="text-left text-sm font-medium text-gray-900 pb-2">Zweck</th>
                        <th class="text-left text-sm font-medium text-gray-900 pb-2">Laufzeit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($category['cookies'] as $cookieName => $cookieDesc)
                    <tr>
                        <td class="py-2 text-sm font-mono">{{ $cookieName }}</td>
                        <td class="py-2 text-sm">{{ $cookieDesc }}</td>
                        <td class="py-2 text-sm">
                            @if($cookieName === 'askproai_session')
                                Sitzung
                            @elseif(in_array($cookieName, ['_ga', '_gid']))
                                2 Jahre
                            @else
                                Variabel
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
        @endforeach

        <h2>3. Rechtsgrundlage</h2>
        <p>
            Die Verwendung von Cookies erfolgt auf Grundlage Ihrer Einwilligung gemäß Art. 6 Abs. 1 lit. a DSGVO. 
            Notwendige Cookies, die für den Betrieb der Website erforderlich sind, werden auf Grundlage unseres 
            berechtigten Interesses gemäß Art. 6 Abs. 1 lit. f DSGVO gesetzt.
        </p>

        <h2>4. Ihre Rechte</h2>
        <p>Sie haben jederzeit das Recht:</p>
        <ul>
            <li>Ihre Cookie-Einstellungen zu ändern</li>
            <li>Ihre Einwilligung zu widerrufen</li>
            <li>Cookies in Ihrem Browser zu blockieren oder zu löschen</li>
            <li>Auskunft über die von uns gespeicherten Daten zu erhalten</li>
        </ul>

        <h2>5. Drittanbieter-Cookies</h2>
        <p>
            Wir verwenden Dienste von Drittanbietern, die möglicherweise eigene Cookies setzen:
        </p>
        <ul>
            @if(config('gdpr.third_party_services.google_analytics.enabled'))
            <li><strong>Google Analytics:</strong> Zur Analyse der Website-Nutzung</li>
            @endif
            @if(config('gdpr.third_party_services.facebook_pixel.enabled'))
            <li><strong>Facebook Pixel:</strong> Für zielgerichtete Werbung</li>
            @endif
        </ul>

        <h2>6. Cookies verwalten</h2>
        <p>
            Sie können Ihre Cookie-Einstellungen jederzeit über unsere 
            <a href="{{ route('portal.privacy') }}" class="text-blue-600 hover:text-blue-800">Datenschutz-Einstellungen</a> 
            verwalten.
        </p>
        
        <p>
            Zusätzlich können Sie Cookies über Ihre Browser-Einstellungen kontrollieren:
        </p>
        <ul>
            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800">Chrome</a></li>
            <li><a href="https://support.mozilla.org/de/kb/cookies-erlauben-und-ablehnen" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800">Firefox</a></li>
            <li><a href="https://support.apple.com/de-de/guide/safari/sfri11471/mac" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800">Safari</a></li>
            <li><a href="https://support.microsoft.com/de-de/windows/l%C3%B6schen-und-verwalten-von-cookies-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800">Edge</a></li>
        </ul>

        <h2>7. Änderungen dieser Richtlinie</h2>
        <p>
            Wir behalten uns vor, diese Cookie-Richtlinie zu aktualisieren, um Änderungen in unseren Praktiken 
            oder aus rechtlichen Gründen widerzuspiegeln. Die aktuelle Version finden Sie immer auf dieser Seite.
        </p>

        <h2>8. Kontakt</h2>
        <p>
            Bei Fragen zu unserer Cookie-Richtlinie wenden Sie sich bitte an:
        </p>
        <address class="not-italic">
            {{ config('gdpr.data_protection_officer.name') }}<br>
            E-Mail: <a href="mailto:{{ config('gdpr.data_protection_officer.email') }}" class="text-blue-600 hover:text-blue-800">{{ config('gdpr.data_protection_officer.email') }}</a><br>
            Telefon: {{ config('gdpr.data_protection_officer.phone') }}<br>
            {{ config('gdpr.data_protection_officer.address') }}
        </address>
    </div>
</div>
@endsection