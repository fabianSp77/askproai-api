@props(['call', 'buttonClass' => ''])

<div class="relative inline-block text-left">
    {{-- Simple Copy Button --}}
    <button 
        type="button"
        onclick="copyCallQuickSummary{{ $call->id }}()"
        class="inline-flex items-center {{ $buttonClass ?: 'px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500' }}"
        title="Anrufdetails kopieren"
    >
        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        Kopieren
    </button>
</div>

<script>
function copyCallQuickSummary{{ $call->id }}() {
    fetch('{{ route('business.calls.format', $call->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ format: 'summary' })
    })
    .then(response => response.json())
    .then(data => {
        navigator.clipboard.writeText(data.formatted).then(() => {
            // Show simple alert or toast
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>Kopiert!';
            button.classList.add('bg-green-50', 'text-green-700', 'border-green-300');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('bg-green-50', 'text-green-700', 'border-green-300');
            }, 2000);
        });
    })
    .catch(err => {
        console.error('Copy failed:', err);
        alert('Kopieren fehlgeschlagen');
    });
}
</script>