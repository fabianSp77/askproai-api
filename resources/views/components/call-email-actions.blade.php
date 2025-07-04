@props(['call'])

<div class="relative inline-block text-left" x-data="{ open: false }">
    <button 
        @click="open = !open"
        @click.away="open = false"
        type="button" 
        class="inline-flex items-center p-1.5 border border-gray-300 rounded-md shadow-sm text-xs bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        aria-label="Email-Aktionen"
    >
        <svg class="h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </button>

    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
    >
        <div class="py-1">
            {{-- Copy to Clipboard --}}
            <button
                @click="copyCallData({{ json_encode([
                    'id' => $call->id,
                    'date' => $call->created_at?->format('d.m.Y H:i'),
                    'phone' => $call->phone_number,
                    'customer' => $call->extracted_name ?? $call->customer?->name ?? 'Unbekannt',
                    'reason' => $call->reason_for_visit ?? '-',
                    'duration' => gmdate('i:s', $call->duration_sec ?? 0),
                    'summary' => $call->summary ?? ''
                ]) }}); open = false;"
                class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            >
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Daten kopieren
            </button>

            {{-- Open in Email Client --}}
            <a
                href="mailto:?subject=Anruf vom {{ $call->created_at?->format('d.m.Y H:i') }} - {{ $call->phone_number }}&body={{ urlencode(
                    "Anrufdetails:\n\n" .
                    "Datum/Zeit: " . ($call->created_at?->format('d.m.Y H:i') ?? '-') . "\n" .
                    "Telefonnummer: " . $call->phone_number . "\n" .
                    "Kunde: " . ($call->extracted_name ?? $call->customer?->name ?? 'Unbekannt') . "\n" .
                    "Anliegen: " . ($call->reason_for_visit ?? '-') . "\n" .
                    "Dauer: " . gmdate('i:s', $call->duration_sec ?? 0) . "\n\n" .
                    "Zusammenfassung:\n" . ($call->summary ?? 'Keine Zusammenfassung verfügbar')
                ) }}"
                @click="open = false"
                class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            >
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                In Email-Programm öffnen
            </a>
        </div>
        
        <div class="py-1">
            {{-- Send via System (Future Feature) --}}
            <button
                @click="alert('Diese Funktion wird in Kürze verfügbar sein'); open = false;"
                class="group flex items-center w-full px-4 py-2 text-sm text-gray-400 cursor-not-allowed"
                disabled
            >
                <svg class="mr-3 h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Über System versenden
                <span class="ml-auto text-xs text-gray-400">Bald verfügbar</span>
            </button>
        </div>
    </div>
</div>

<script>
function copyCallData(data) {
    const text = `Anrufdetails:
    
Datum/Zeit: ${data.date}
Telefonnummer: ${data.phone}
Kunde: ${data.customer}
Anliegen: ${data.reason}
Dauer: ${data.duration}

Zusammenfassung:
${data.summary || 'Keine Zusammenfassung verfügbar'}`;
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            // Show success toast
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { 
                    message: 'Anrufdaten wurden in die Zwischenablage kopiert', 
                    type: 'success' 
                } 
            }));
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        window.dispatchEvent(new CustomEvent('show-toast', { 
            detail: { 
                message: 'Anrufdaten wurden in die Zwischenablage kopiert', 
                type: 'success' 
            } 
        }));
    }
}
</script>