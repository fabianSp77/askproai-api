@props(['call'])

<div class="relative inline-block text-left" x-data="{ open: false }">
    <button 
        id="email_actions_button"
        name="email_actions"
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
                id="copy_call_data_button"
                name="copy_call_data"
                type="button"
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
            {{-- Send via System --}}
            <button
                id="send_via_system_button"
                name="send_via_system"
                type="button"
                @click="openSendDialog(); open = false;"
                class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
            >
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Über System versenden
            </button>
        </div>
    </div>
</div>

{{-- Email Send Dialog --}}
<div x-data="{ showSendDialog: false, recipients: [''], message: '', sending: false }" 
     x-show="showSendDialog"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     @send-dialog.window="showSendDialog = true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showSendDialog" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
             @click="showSendDialog = false"></div>

        <div x-show="showSendDialog"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Anrufzusammenfassung versenden
                    </h3>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Empfänger
                        </label>
                        <template x-for="(recipient, index) in recipients" :key="index">
                            <div class="flex mb-2">
                                <input 
                                    type="email" 
                                    x-model="recipients[index]"
                                    placeholder="email@example.com"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    required
                                >
                                <button 
                                    type="button"
                                    @click="recipients.splice(index, 1)"
                                    x-show="recipients.length > 1"
                                    class="ml-2 text-red-600 hover:text-red-800"
                                >
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button 
                            type="button"
                            @click="recipients.push('')"
                            class="text-sm text-blue-600 hover:text-blue-800"
                        >
                            + Weiteren Empfänger hinzufügen
                        </button>
                        
                        <label class="block text-sm font-medium text-gray-700 mt-4 mb-2">
                            Nachricht (optional)
                        </label>
                        <textarea 
                            x-model="message"
                            rows="3"
                            placeholder="Zusätzliche Nachricht für den Empfänger..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        ></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button 
                    type="button"
                    @click="sendCallSummary()"
                    :disabled="sending || recipients.filter(r => r).length === 0"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span x-show="!sending">Senden</span>
                    <span x-show="sending">Wird gesendet...</span>
                </button>
                <button 
                    type="button"
                    @click="showSendDialog = false; recipients = ['']; message = '';"
                    :disabled="sending"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm"
                >
                    Abbrechen
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openSendDialog() {
    window.dispatchEvent(new CustomEvent('send-dialog'));
}

function sendCallSummary() {
    const dialog = document.querySelector('[x-data]');
    const data = dialog._x_dataStack[0];
    
    // Filter out empty recipients
    const validRecipients = data.recipients.filter(r => r.trim() !== '');
    
    if (validRecipients.length === 0) {
        alert('Bitte geben Sie mindestens einen Empfänger ein.');
        return;
    }
    
    data.sending = true;
    
    // Make API call
    fetch(`/business/api/calls/{{ $call->id }}/send-summary`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            recipients: validRecipients,
            message: data.message
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { 
                    message: result.message, 
                    type: 'success' 
                } 
            }));
            data.showSendDialog = false;
            data.recipients = [''];
            data.message = '';
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { 
                    message: result.message || 'Fehler beim Senden', 
                    type: 'error' 
                } 
            }));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.dispatchEvent(new CustomEvent('show-toast', { 
            detail: { 
                message: 'Fehler beim Senden der E-Mail', 
                type: 'error' 
            } 
        }));
    })
    .finally(() => {
        data.sending = false;
    });
}

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