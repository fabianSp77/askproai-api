<div class="space-y-6">
    {{-- Preview Card --}}
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl p-6 border border-purple-200 dark:border-gray-700">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $record->customer?->name ?? 'Unbekannter Anrufer' }}</h3>
                <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Telefon:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $record->from_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Dauer:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ gmdate('i:s', $record->duration_sec ?? 0) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Datum:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $record->start_timestamp?->format('d.m.Y H:i') ?? $record->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Stimmung:</span>
                        @php
                            $sentiment = $record->analysis['sentiment'] ?? 'neutral';
                            $sentimentClass = match($sentiment) {
                                'positive' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'negative' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            };
                            $sentimentEmoji = match($sentiment) {
                                'positive' => '😊',
                                'negative' => '😞',
                                default => '😐'
                            };
                            $sentimentText = match($sentiment) {
                                'positive' => 'Positiv',
                                'negative' => 'Negativ',
                                default => 'Neutral'
                            };
                        @endphp
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sentimentClass }}">
                            {{ $sentimentEmoji }} {{ $sentimentText }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Share Options --}}
    <div class="space-y-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Teilen via:</h4>
        
        <div class="grid grid-cols-1 gap-3">
            {{-- Email Button --}}
            <button 
                onclick="shareViaEmail{{ $record->id }}()"
                class="flex items-center justify-between px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md group"
            >
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <div class="text-left">
                        <div class="font-medium">E-Mail versenden</div>
                        <div class="text-xs opacity-90">Öffnet E-Mail-Programm mit Details</div>
                    </div>
                </div>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            
            {{-- WhatsApp Button --}}
            <a 
                href="https://wa.me/?text={{ urlencode(str_replace('\\n', "\n", $whatsappMessage)) }}"
                target="_blank"
                class="flex items-center justify-between px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md group"
            >
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <div class="text-left">
                        <div class="font-medium">WhatsApp</div>
                        <div class="text-xs opacity-90">Schnell per Messenger teilen</div>
                    </div>
                </div>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
            
            {{-- Copy Link Button --}}
            <button 
                onclick="copyToClipboard('share-link-{{ $record->id }}')"
                class="flex items-center justify-between px-4 py-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-900 dark:text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md group"
            >
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    <div class="text-left">
                        <div class="font-medium">Link kopieren</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">In die Zwischenablage</div>
                    </div>
                </div>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            
            {{-- Copy Summary Button --}}
            <button 
                onclick="copySummary{{ $record->id }}()"
                class="flex items-center justify-between px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md group"
            >
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div class="text-left">
                        <div class="font-medium">Zusammenfassung kopieren</div>
                        <div class="text-xs opacity-90">Formatierte Details als Text</div>
                    </div>
                </div>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
    
    {{-- Public Link Display --}}
    <div class="mt-6 pt-6 border-t dark:border-gray-700">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Direkter Link:
        </label>
        <div class="flex gap-2">
            <input 
                type="text" 
                id="share-link-{{ $record->id }}" 
                value="{{ $publicUrl }}" 
                readonly 
                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-900 text-sm font-mono"
            />
        </div>
    </div>
    
    @if($record->audio_url)
    <div class="pt-6 border-t dark:border-gray-700">
        <a 
            href="{{ $record->audio_url }}"
            download="anruf-{{ $record->id }}-{{ $record->created_at->format('Y-m-d') }}.mp3"
            class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-colors w-full"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Audio-Datei herunterladen
        </a>
    </div>
    @endif
</div>

<script>
    function copyToClipboard(elementId) {
        const input = document.getElementById(elementId);
        input.select();
        input.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            
            // Filament notification
            new FilamentNotification()
                .title('✅ Link wurde in die Zwischenablage kopiert!')
                .success()
                .send();
        } catch (err) {
            console.error('Kopieren fehlgeschlagen:', err);
        }
    }
    
    function shareViaEmail{{ $record->id }}() {
        const subject = @json($emailSubject);
        
        const emailBody = `🎧 ANRUFAUFZEICHNUNG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📊 ANRUF-ÜBERSICHT
────────────────────────────────────────────────
👤 Anrufer:         ${@json($customerName)}
📞 Telefonnummer:   ${@json($phoneNumber)}
📅 Datum:           ${@json($callDate . ' um ' . $callTime . ' Uhr')}
⏱️  Dauer:           ${@json($duration . ' Minuten')}
🎭 Stimmung:        ${@json($sentimentEmoji . ' ' . $sentimentText)}

💡 ANRUF ANHÖREN
────────────────────────────────────────────────
Klicken Sie auf den folgenden Link, um die 
Aufzeichnung anzuhören:

🔗 ${@json($publicUrl)}

📋 WEITERE INFORMATIONEN
────────────────────────────────────────────────
Diese Aufzeichnung wurde automatisch erstellt und 
steht Ihnen zur Qualitätssicherung und Dokumentation 
zur Verfügung.

Bei Fragen wenden Sie sich bitte an Ihren Administrator.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Powered by AskProAI • ${new Date().getFullYear()}
Diese E-Mail wurde automatisch generiert.`;
        
        window.location.href = "mailto:?subject=" + encodeURIComponent(subject) + "&body=" + encodeURIComponent(emailBody);
    }
    
    function copySummary{{ $record->id }}() {
        const summary = `📞 ANRUF-ZUSAMMENFASSUNG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Anrufer: ${@json($customerName)}
Telefon: ${@json($phoneNumber)}
Datum: ${@json($callDate . ' um ' . $callTime . ' Uhr')}
Dauer: ${@json($duration . ' Minuten')}
Stimmung: ${@json($sentimentEmoji . ' ' . $sentimentText)}

Link zur Aufzeichnung:
${@json($publicUrl)}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Powered by AskProAI`;
        
        const textarea = document.createElement('textarea');
        textarea.value = summary;
        textarea.style.position = 'fixed';
        textarea.style.left = '-999999px';
        document.body.appendChild(textarea);
        textarea.select();
        
        try {
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            new FilamentNotification()
                .title('✅ Zusammenfassung wurde in die Zwischenablage kopiert!')
                .success()
                .send();
        } catch (err) {
            console.error('Kopieren fehlgeschlagen:', err);
            document.body.removeChild(textarea);
        }
    }
</script>