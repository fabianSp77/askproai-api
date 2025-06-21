@extends('layouts.public')

@section('title', 'Datenschutzerklärung')

@section('content')
<div class="bg-white">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Datenschutzerklärung</h1>
        
        <div class="prose prose-lg text-gray-600">
            <p class="mb-6">Stand: {{ date('d.m.Y') }}</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">1. Datenschutz auf einen Blick</h2>
            
            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Allgemeine Hinweise</h3>
            <p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Datenerfassung auf dieser Website</h3>
            <p><strong>Wer ist verantwortlich für die Datenerfassung auf dieser Website?</strong></p>
            <p>Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten können Sie dem Impressum dieser Website entnehmen.</p>

            <p class="mt-4"><strong>Wie erfassen wir Ihre Daten?</strong></p>
            <p>Ihre Daten werden zum einen dadurch erhoben, dass Sie uns diese mitteilen. Hierbei kann es sich z.B. um Daten handeln, die Sie bei der Terminbuchung angeben.</p>
            <p>Andere Daten werden automatisch oder nach Ihrer Einwilligung beim Besuch der Website durch unsere IT-Systeme erfasst. Das sind vor allem technische Daten (z.B. Internetbrowser, Betriebssystem oder Uhrzeit des Seitenaufrufs).</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">2. Hosting</h2>
            <p>Wir hosten die Inhalte unserer Website bei folgendem Anbieter:</p>
            
            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Externes Hosting</h3>
            <p>Diese Website wird extern gehostet. Die personenbezogenen Daten, die auf dieser Website erfasst werden, werden auf den Servern des Hosters gespeichert. Die Daten werden ausschließlich auf Servern in Deutschland verarbeitet.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">3. Allgemeine Hinweise und Pflichtinformationen</h2>
            
            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Datenschutz</h3>
            <p>Die Betreiber dieser Seiten nehmen den Schutz Ihrer persönlichen Daten sehr ernst. Wir behandeln Ihre personenbezogenen Daten vertraulich und entsprechend der gesetzlichen Datenschutzvorschriften sowie dieser Datenschutzerklärung.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Hinweis zur verantwortlichen Stelle</h3>
            <p>Die verantwortliche Stelle für die Datenverarbeitung auf dieser Website ist:</p>
            <div class="bg-gray-50 p-4 rounded-lg mt-4">
                <p>AskProAI GmbH<br>
                Musterstraße 1<br>
                12345 Berlin<br>
                Deutschland</p>
                
                <p class="mt-2">
                Telefon: +49 (0) 30 12345678<br>
                E-Mail: datenschutz@askproai.de
                </p>
            </div>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Speicherdauer</h3>
            <p>Soweit innerhalb dieser Datenschutzerklärung keine speziellere Speicherdauer genannt wurde, verbleiben Ihre personenbezogenen Daten bei uns, bis der Zweck für die Datenverarbeitung entfällt.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">SSL- bzw. TLS-Verschlüsselung</h3>
            <p>Diese Seite nutzt aus Sicherheitsgründen und zum Schutz der Übertragung vertraulicher Inhalte eine SSL- bzw. TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennen Sie daran, dass die Adresszeile des Browsers von „http://" auf „https://" wechselt und an dem Schloss-Symbol in Ihrer Browserzeile.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">4. Datenerfassung auf dieser Website</h2>
            
            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Cookies</h3>
            <p>Unsere Internetseiten verwenden so genannte „Cookies". Cookies sind kleine Textdateien und richten auf Ihrem Endgerät keinen Schaden an. Sie werden entweder vorübergehend für die Dauer einer Sitzung (Session-Cookies) oder dauerhaft (permanente Cookies) auf Ihrem Endgerät gespeichert.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Kontaktformular</h3>
            <p>Wenn Sie uns per Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert.</p>

            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Anfrage per E-Mail, Telefon oder Telefax</h3>
            <p>Wenn Sie uns per E-Mail, Telefon oder Telefax kontaktieren, wird Ihre Anfrage inklusive aller daraus hervorgehenden personenbezogenen Daten (Name, Anfrage) zum Zwecke der Bearbeitung Ihres Anliegens bei uns gespeichert und verarbeitet.</p>

            <h2 class="text-2xl font-semibold text-gray-900 mt-8 mb-4">5. Plugins und Tools</h2>
            
            <h3 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Google Fonts</h3>
            <p>Diese Seite nutzt zur einheitlichen Darstellung von Schriftarten so genannte Google Fonts, die von Google bereitgestellt werden. Die Google Fonts sind lokal installiert. Eine Verbindung zu Servern von Google findet dabei nicht statt.</p>

            <div class="mt-12 p-6 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-700">
                    <strong>Hinweis:</strong> Diese Datenschutzerklärung ist ein Muster und muss an die spezifischen Gegebenheiten Ihres Unternehmens angepasst werden. Bitte lassen Sie diese von einem Rechtsanwalt prüfen.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection