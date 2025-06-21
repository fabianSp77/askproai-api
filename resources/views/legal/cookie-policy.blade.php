@extends('layouts.public')

@section('title', 'Cookie-Richtlinie')

@section('content')
<div class="bg-white">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Cookie-Richtlinie</h1>
        
        <div class="prose prose-lg text-gray-600">
            <p class="mb-6">Stand: {{ date('d.m.Y') }}</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Was sind Cookies?</h2>
            <p>Cookies sind kleine Textdateien, die auf Ihrem Computer oder Mobilgerät gespeichert werden, wenn Sie unsere Website besuchen. Sie werden weithin verwendet, um Websites funktionsfähig zu machen oder effizienter arbeiten zu lassen sowie um Informationen an die Eigentümer der Website zu liefern.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Wie verwenden wir Cookies?</h2>
            <p>Wir verwenden Cookies für verschiedene Zwecke:</p>
            
            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Notwendige Cookies</h3>
            <p>Diese Cookies sind für den Betrieb unserer Website unerlässlich. Sie ermöglichen es Ihnen, sich auf unserer Website zu bewegen und ihre Funktionen zu nutzen. Ohne diese Cookies können bestimmte Dienste nicht bereitgestellt werden.</p>
            
            <div class="bg-gray-50 p-4 rounded-lg mt-4">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Cookie-Name</th>
                            <th class="text-left py-2">Zweck</th>
                            <th class="text-left py-2">Ablauf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td class="py-2">askproai_session</td>
                            <td class="py-2">Verwaltet Ihre Browsersitzung</td>
                            <td class="py-2">Nach Sitzungsende</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2">XSRF-TOKEN</td>
                            <td class="py-2">Sicherheit - Schutz vor Cross-Site Request Forgery</td>
                            <td class="py-2">2 Stunden</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2">cookie_consent</td>
                            <td class="py-2">Speichert Ihre Cookie-Einstellungen</td>
                            <td class="py-2">1 Jahr</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Funktionale Cookies</h3>
            <p>Diese Cookies ermöglichen es der Website, erweiterte Funktionalität und Personalisierung bereitzustellen. Sie können von uns oder von Drittanbietern gesetzt werden, deren Dienste wir auf unseren Seiten hinzugefügt haben.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Analytische Cookies</h3>
            <p>Diese Cookies helfen uns zu verstehen, wie Besucher mit unserer Website interagieren, indem Informationen anonym gesammelt und gemeldet werden. Dies hilft uns, unsere Website und Dienste kontinuierlich zu verbessern.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Wie kann ich Cookies kontrollieren?</h2>
            <p>Sie haben das Recht zu entscheiden, ob Sie Cookies akzeptieren oder ablehnen möchten. Sie können Ihre Cookie-Einstellungen jederzeit ändern:</p>
            
            <ul class="list-disc pl-6 mt-4">
                <li>Über unser Cookie-Einstellungs-Tool (wird beim ersten Besuch angezeigt)</li>
                <li>Durch Änderung Ihrer Browser-Einstellungen</li>
                <li>Durch Löschen bereits gespeicherter Cookies</li>
            </ul>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-6">
                <p class="text-sm">
                    <strong>Hinweis:</strong> Wenn Sie Cookies deaktivieren, können Sie möglicherweise nicht alle Funktionen unserer Website nutzen.
                </p>
            </div>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Browser-Einstellungen</h2>
            <p>Die meisten Webbrowser erlauben eine gewisse Kontrolle über Cookies durch die Browser-Einstellungen. So finden Sie Informationen zum Verwalten von Cookies in Ihrem Browser:</p>
            
            <ul class="list-disc pl-6 mt-4">
                <li><a href="https://support.google.com/chrome/answer/95647" class="text-blue-600 hover:underline" target="_blank">Chrome</a></li>
                <li><a href="https://support.mozilla.org/de/kb/cookies-erlauben-und-ablehnen" class="text-blue-600 hover:underline" target="_blank">Firefox</a></li>
                <li><a href="https://support.apple.com/de-de/guide/safari/sfri11471/mac" class="text-blue-600 hover:underline" target="_blank">Safari</a></li>
                <li><a href="https://support.microsoft.com/de-de/help/17442/windows-internet-explorer-delete-manage-cookies" class="text-blue-600 hover:underline" target="_blank">Internet Explorer</a></li>
                <li><a href="https://help.opera.com/de/latest/web-preferences/#cookies" class="text-blue-600 hover:underline" target="_blank">Opera</a></li>
            </ul>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">Kontakt</h2>
            <p>Wenn Sie Fragen zu unserer Cookie-Richtlinie haben, kontaktieren Sie uns bitte:</p>
            
            <div class="bg-gray-50 p-4 rounded-lg mt-4">
                <p>
                    E-Mail: datenschutz@askproai.de<br>
                    Telefon: +49 (0) 30 12345678<br>
                    Adresse: AskProAI GmbH, Musterstraße 1, 12345 Berlin
                </p>
            </div>

            <div class="mt-12 flex justify-center">
                <button onclick="window.location.href='/'" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    Cookie-Einstellungen anpassen
                </button>
            </div>
        </div>
    </div>
</div>
@endsection