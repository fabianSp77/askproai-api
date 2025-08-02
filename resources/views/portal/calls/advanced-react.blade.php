@extends('portal.layouts.unified')

@section('page-title', 'Anrufe')

@section('content')
<div id="calls-app-root" class="min-h-screen">
    <!-- React app will mount here -->
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Lade erweiterte Anrufansicht...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
// Import React and dependencies from CDN
import React from 'https://esm.sh/react@18.2.0';
import ReactDOM from 'https://esm.sh/react-dom@18.2.0/client';
import { BrowserRouter } from 'https://esm.sh/react-router-dom@6.22.0';

// Wait for the page to load
window.addEventListener('load', async () => {
    try {
        // Dynamically import the bundled app
        const CallsModule = await import('{{ asset("build/assets/portal-calls-DM1d7S97.js") }}');
        
        // The module auto-mounts, but we need to wrap it
        // Since it's already mounted, we need a different approach
        
        // Check if the app mounted
        const root = document.getElementById('calls-index-root');
        if (!root) {
            // Create the expected root element
            const appRoot = document.getElementById('calls-app-root');
            const callsRoot = document.createElement('div');
            callsRoot.id = 'calls-index-root';
            callsRoot.className = 'min-h-screen';
            appRoot.innerHTML = '';
            appRoot.appendChild(callsRoot);
            
            // Re-import to trigger mount
            await import('{{ asset("build/assets/portal-calls-DM1d7S97.js") }}');
        }
    } catch (error) {
        console.error('Failed to load React app:', error);
        document.getElementById('calls-app-root').innerHTML = `
            <div class="flex items-center justify-center min-h-screen">
                <div class="text-center">
                    <div class="text-red-600 mb-4">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600">Fehler beim Laden der Anwendung</p>
                    <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Neu laden
                    </button>
                </div>
            </div>
        `;
    }
});
</script>
@endpush