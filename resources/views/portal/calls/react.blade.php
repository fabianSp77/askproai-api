@extends('portal.layouts.unified')

@section('page-title', 'Anrufe')

@section('content')
<div id="calls-index-root" class="min-h-screen">
    <!-- React app will mount here -->
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Lade Anrufe...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Fix for Router context -->
<script type="module">
import React from 'https://esm.sh/react@18';
import ReactDOM from 'https://esm.sh/react-dom@18/client';
import { BrowserRouter } from 'https://esm.sh/react-router-dom@6';

// Wrap the app with BrowserRouter
const originalMount = window.mountCallsApp;
if (originalMount) {
    window.mountCallsApp = function(component) {
        return React.createElement(BrowserRouter, { basename: '/business' }, component);
    };
}
</script>
<!-- Load React app -->
<script src="{{ asset('build/assets/portal-calls-Bpj8EEVP.js') }}" type="module"></script>
@endpush