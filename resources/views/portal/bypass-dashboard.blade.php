<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Dashboard - Bypass Mode</title>
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
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="container mx-auto px-4 py-4">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">
                        🚀 Portal Dashboard (Bypass Mode)
                    </h1>
                    <div class="text-sm text-gray-600">
                        @if($authenticated)
                            <span class="text-green-600">✅ Authenticated as: {{ $user->name }}</span>
                        @else
                            <span class="text-red-600">❌ Not Authenticated</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
            @if(!$authenticated || !$user)
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
                    <strong>Warnung:</strong> Sie sind nicht authentifiziert. 
                    <a href="{{ url('/business/bypass/login') }}" class="underline">Erneut einloggen</a>
                </div>
            @endif

            <!-- Feature Cards -->
            <div class="grid md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-blue-500 text-3xl mb-2">🎵</div>
                    <h3 class="font-semibold">Audio Player</h3>
                    <p class="text-sm text-gray-600">Inline-Wiedergabe</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-green-500 text-3xl mb-2">📄</div>
                    <h3 class="font-semibold">Transkripte</h3>
                    <p class="text-sm text-gray-600">Ein-/Ausklappbar</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-purple-500 text-3xl mb-2">🌐</div>
                    <h3 class="font-semibold">Übersetzung</h3>
                    <p class="text-sm text-gray-600">12 Sprachen</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="text-orange-500 text-3xl mb-2">💳</div>
                    <h3 class="font-semibold">Stripe</h3>
                    <p class="text-sm text-gray-600">Zahlungen</p>
                </div>
            </div>

            <!-- Calls Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-xl font-semibold">📞 Letzte Anrufe</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anrufer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dauer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($calls as $call)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $call->from_number }}</div>
                                    @if($call->customer_name)
                                        <div class="text-sm text-gray-500">{{ $call->customer_name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $call->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ gmdate("i:s", $call->duration_sec ?? 0) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ $call->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        @if($call->recording_url)
                                        <button onclick="toggleAudio('{{ $call->id }}', '{{ $call->recording_url }}')" 
                                                class="text-blue-600 hover:text-blue-900 p-2" title="Audio abspielen">
                                            <i id="audio-icon-{{ $call->id }}" class="fas fa-play"></i>
                                        </button>
                                        @endif
                                        
                                        @if($call->transcript)
                                        <button onclick="toggleTranscript('{{ $call->id }}')" 
                                                class="text-green-600 hover:text-green-900 p-2" title="Transkript">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                        @endif
                                        
                                        @if($call->transcript)
                                        <button onclick="translateDemo('{{ $call->id }}')" 
                                                class="text-purple-600 hover:text-purple-900 p-2" title="Übersetzen">
                                            <i class="fas fa-globe"></i>
                                        </button>
                                        @endif
                                        
                                        <button onclick="showCallDetails('{{ $call->id }}')" 
                                                class="text-gray-600 hover:text-gray-900 p-2" title="Details">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Transcript Row -->
                            <tr id="transcript-{{ $call->id }}" class="hidden">
                                <td colspan="5" class="px-6 py-4 bg-gray-50">
                                    <div class="transcript-content" id="transcript-content-{{ $call->id }}">
                                        <div class="flex justify-between items-start mb-2">
                                            <h4 class="font-semibold text-sm">Transkript</h4>
                                            <button onclick="copyTranscript('{{ $call->id }}')" class="text-sm text-blue-600">
                                                <i class="fas fa-copy"></i> Kopieren
                                            </button>
                                        </div>
                                        <div class="bg-white p-3 rounded border text-sm whitespace-pre-wrap" id="transcript-text-{{ $call->id }}">{{ $call->transcript }}</div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    Keine Anrufe gefunden
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Info Box -->
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">⚠️ Bypass Mode Information</h3>
                <p class="text-yellow-700">
                    Sie befinden sich im Bypass-Modus. Diese Ansicht umgeht die normale Authentifizierung.
                    Normale Portal-Links funktionieren möglicherweise nicht.
                </p>
                <div class="mt-3">
                    <a href="{{ url('/portal-debug-auth.php') }}" class="text-yellow-800 underline">
                        Debug-Informationen anzeigen →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden audio element -->
    <audio id="audio-player" style="display: none;"></audio>

    <script>
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

        function copyTranscript(callId) {
            const text = document.getElementById('transcript-text-' + callId).innerText;
            navigator.clipboard.writeText(text);
            alert('Transkript kopiert!');
        }

        function translateDemo(callId) {
            alert('Übersetzungsfunktion:\n\nIn der echten App würde hier das Transkript übersetzt werden.\nUnterstützte Sprachen: DE, EN, ES, FR, IT, PT, ZH, JA, KO, AR, RU, TR');
        }

        function showCallDetails(callId) {
            alert('Call-Details für ID: ' + callId + '\n\nIn der echten App öffnet sich hier die React-basierte Detail-Ansicht.');
        }
    </script>
</body>
</html>