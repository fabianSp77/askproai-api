<?php
// Standalone demo page - no Laravel session required

// Basic PHP session for demo
session_start();

// Direct database connection
$db = new mysqli('127.0.0.1', 'askproai_user', 'lkZ57Dju9EDjrMxn', 'askproai_db');

// Get some sample calls
$query = "SELECT c.*, comp.name as company_name 
          FROM calls c 
          JOIN companies comp ON c.company_id = comp.id 
          WHERE c.transcript IS NOT NULL 
          AND c.recording_url IS NOT NULL 
          ORDER BY c.created_at DESC 
          LIMIT 5";

$result = $db->query($query);
$calls = [];
while ($row = $result->fetch_assoc()) {
    $calls[] = $row;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AskProAI - Feature Demo (Öffentlich)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .transcript-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .transcript-content.expanded {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h1 class="text-3xl font-bold mb-4">🚀 AskProAI Feature Demo</h1>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-blue-700">
                    <strong>Dies ist eine öffentliche Demo-Seite</strong> - keine Anmeldung erforderlich!
                    Testen Sie alle neuen Features direkt hier.
                </p>
            </div>
        </div>

        <!-- Feature Overview -->
        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-blue-500 text-3xl mb-2">🎵</div>
                <h3 class="font-semibold">Audio-Player</h3>
                <p class="text-sm text-gray-600">Aufnahmen direkt abspielen</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-green-500 text-3xl mb-2">📄</div>
                <h3 class="font-semibold">Transkript-Toggle</h3>
                <p class="text-sm text-gray-600">Ein-/Ausklappbare Transkripte</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-purple-500 text-3xl mb-2">🌐</div>
                <h3 class="font-semibold">Übersetzung</h3>
                <p class="text-sm text-gray-600">Transkripte übersetzen</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <div class="text-orange-500 text-3xl mb-2">💳</div>
                <h3 class="font-semibold">Stripe Integration</h3>
                <p class="text-sm text-gray-600">Guthaben aufladen</p>
            </div>
        </div>

        <!-- Calls Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold">📞 Aktuelle Anrufe</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anrufer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dauer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($calls as $index => $call): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($call['from_number']) ?></div>
                                    <?php if ($call['customer_name']): ?>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($call['customer_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d.m.Y H:i', strtotime($call['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= gmdate("i:s", $call['duration_sec'] ?? 0) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <?= htmlspecialchars($call['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <!-- Audio Player Button -->
                                    <?php if ($call['recording_url']): ?>
                                    <button onclick="toggleAudio('<?= $call['id'] ?>', '<?= htmlspecialchars($call['recording_url']) ?>')" 
                                            class="text-blue-600 hover:text-blue-900 p-2" title="Audio abspielen">
                                        <i id="audio-icon-<?= $call['id'] ?>" class="fas fa-play"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Transcript Toggle -->
                                    <?php if ($call['transcript']): ?>
                                    <button onclick="toggleTranscript('<?= $call['id'] ?>')" 
                                            class="text-green-600 hover:text-green-900 p-2" title="Transkript anzeigen">
                                        <i class="fas fa-file-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Translate Button -->
                                    <?php if ($call['transcript']): ?>
                                    <button onclick="translateTranscript('<?= $call['id'] ?>')" 
                                            class="text-purple-600 hover:text-purple-900 p-2" title="Übersetzen">
                                        <i class="fas fa-globe"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Details Button -->
                                    <button onclick="showDetails('<?= $call['id'] ?>')" 
                                            class="text-gray-600 hover:text-gray-900 p-2" title="Details">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Transcript Row (Hidden by default) -->
                        <tr id="transcript-<?= $call['id'] ?>" class="hidden">
                            <td colspan="5" class="px-6 py-4 bg-gray-50">
                                <div class="transcript-content" id="transcript-content-<?= $call['id'] ?>">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-semibold text-sm">Transkript</h4>
                                        <button onclick="copyTranscript('<?= $call['id'] ?>')" class="text-sm text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-copy"></i> Kopieren
                                        </button>
                                    </div>
                                    <div class="bg-white p-3 rounded border text-sm whitespace-pre-wrap" id="transcript-text-<?= $call['id'] ?>">
                                        <?= nl2br(htmlspecialchars($call['transcript'])) ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stripe Demo Section -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">💳 Stripe Integration Demo</h2>
            <div class="grid md:grid-cols-4 gap-4">
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" onclick="showStripeDemo('10')">
                    10€ aufladen
                </button>
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" onclick="showStripeDemo('25')">
                    25€ aufladen
                </button>
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" onclick="showStripeDemo('50')">
                    50€ aufladen
                </button>
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" onclick="showStripeDemo('100')">
                    100€ aufladen
                </button>
            </div>
            <div class="mt-4 text-sm text-gray-600">
                <p><strong>Test-Kreditkarte:</strong> 4242 4242 4242 4242</p>
                <p>Ablauf: Beliebiges zukünftiges Datum | CVV: Beliebige 3 Ziffern</p>
            </div>
        </div>
    </div>

    <!-- Hidden audio element -->
    <audio id="audio-player" style="display: none;"></audio>

    <script>
        let currentAudio = null;
        let currentAudioId = null;

        function toggleAudio(callId, url) {
            const audio = document.getElementById('audio-player');
            const icon = document.getElementById('audio-icon-' + callId);
            
            if (currentAudioId === callId && !audio.paused) {
                audio.pause();
                icon.className = 'fas fa-play';
                currentAudioId = null;
            } else {
                if (currentAudioId) {
                    document.getElementById('audio-icon-' + currentAudioId).className = 'fas fa-play';
                }
                audio.src = url;
                audio.play();
                icon.className = 'fas fa-pause';
                currentAudioId = callId;
            }
            
            audio.onended = function() {
                icon.className = 'fas fa-play';
                currentAudioId = null;
            };
        }

        function toggleTranscript(callId) {
            const row = document.getElementById('transcript-' + callId);
            const content = document.getElementById('transcript-content-' + callId);
            
            if (row.classList.contains('hidden')) {
                row.classList.remove('hidden');
                setTimeout(() => content.classList.add('expanded'), 10);
            } else {
                content.classList.remove('expanded');
                setTimeout(() => row.classList.add('hidden'), 300);
            }
        }

        function translateTranscript(callId) {
            const textElement = document.getElementById('transcript-text-' + callId);
            const originalText = textElement.innerText;
            
            // Demo translation (in real app, this would call the API)
            const translations = {
                'Hello': 'Hallo',
                'appointment': 'Termin',
                'customer': 'Kunde',
                'thank you': 'Danke',
                'call': 'Anruf'
            };
            
            let translatedText = originalText;
            for (const [en, de] of Object.entries(translations)) {
                translatedText = translatedText.replace(new RegExp(en, 'gi'), de);
            }
            
            if (translatedText === originalText) {
                translatedText = '[Übersetzt] ' + originalText;
            }
            
            textElement.innerHTML = translatedText.replace(/\n/g, '<br>');
            
            // Show notification
            alert('Transkript wurde übersetzt! (Demo - nutzt einfache Wort-Ersetzung)');
        }

        function copyTranscript(callId) {
            const text = document.getElementById('transcript-text-' + callId).innerText;
            navigator.clipboard.writeText(text);
            alert('Transkript in Zwischenablage kopiert!');
        }

        function showDetails(callId) {
            alert('Call-Detail-Ansicht für Call ID: ' + callId + '\n\nIn der echten App öffnet sich hier die neue React-basierte Detail-Seite mit allen Informationen strukturiert dargestellt.');
        }

        function showStripeDemo(amount) {
            alert('Stripe Checkout Demo\n\nBetrag: ' + amount + '€\n\nIn der echten App öffnet sich hier das Stripe Checkout Formular.');
        }
    </script>
</body>
</html>