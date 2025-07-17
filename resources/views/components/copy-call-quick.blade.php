@props(['call'])

<button 
    type="button"
    @click="copyQuickInfo({{ $call->id }})"
    class="text-gray-400 hover:text-gray-600"
    title="Anruf-Details kopieren"
    x-data="{
        async copyQuickInfo(callId) {
            try {
                const response = await fetch(`/business/calls/${callId}/format`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ format: 'summary' })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    await navigator.clipboard.writeText(data.formatted);
                    
                    // Show toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 z-50 bg-green-50 border-l-4 border-green-400 p-4 rounded-md shadow-lg';
                    toast.innerHTML = `
                        <div class='flex'>
                            <div class='flex-shrink-0'>
                                <svg class='h-5 w-5 text-green-400' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='currentColor'>
                                    <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd' />
                                </svg>
                            </div>
                            <div class='ml-3'>
                                <p class='text-sm font-medium text-green-800'>Anruf-Details kopiert!</p>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        }
    }"
>
    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
    </svg>
</button>