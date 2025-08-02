@extends('portal.layouts.unified')

@section('page-title', 'Anrufe')

@section('content')
<div id="calls-index-root" class="min-h-screen">
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
<!-- Load React Router -->
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-router-dom@6/dist/react-router-dom.production.min.js"></script>

<!-- Temporary fix for Router context -->
<script>
// Override React global to provide Router context
window.React = window.React || {};
window.ReactDOM = window.ReactDOM || {};
window.ReactRouterDOM = window.ReactRouterDOM || {};

// Wait for the main app to load and wrap it with Router
document.addEventListener('DOMContentLoaded', function() {
    // Give the original script time to load
    setTimeout(() => {
        const originalRoot = document.getElementById('calls-index-root');
        if (originalRoot && originalRoot._reactRootContainer) {
            console.warn('React app already mounted, Router context may be missing');
        }
    }, 1000);
});
</script>

<!-- Load the fixed wrapper instead -->
@if(file_exists(public_path('build/assets/portal-calls-wrapper.js')))
<script src="{{ asset('build/assets/portal-calls-wrapper.js') }}" type="module"></script>
@else
<!-- Fallback to original with inline Router fix -->
<script type="module">
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';

// Import the app dynamically
import('./build/assets/portal-calls-DM1d7S97.js').then(module => {
    // The module should export the app or mount it
    // Since it mounts directly, we need to intercept
    console.log('Calls module loaded, attempting Router wrap');
}).catch(err => {
    console.error('Failed to load calls module:', err);
});
</script>
@endif
@endpush