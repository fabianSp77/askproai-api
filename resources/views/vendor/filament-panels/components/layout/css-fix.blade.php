{{-- Emergency CSS Fix for Filament Layout --}}
<link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/filament-hotfix.css') }}">
<link rel="stylesheet" href="{{ asset('css/filament-hotfix-override.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-emergency-fix.css') }}">

{{-- Dropdown Close Fix - Must load AFTER emergency fixes --}}
<link rel="stylesheet" href="{{ asset('css/dropdown-close-fix.css') }}">

<style>
    /* Emergency inline styles to ensure basic layout */
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        opacity: 1 !important;
        visibility: visible !important;
        background-color: #f9fafb !important;
    }
    
    /* Force everything to be visible */
    body * {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Remove any potential overlays */
    .loading-overlay,
    .spinner-overlay,
    [wire\:loading] {
        display: none !important;
    }
    
    .fi-layout {
        display: flex;
        min-height: 100vh;
    }
    
    /* Ensure sidebar is visible */
    .fi-sidebar {
        width: 20rem;
        background: #fff;
        border-right: 1px solid #e5e7eb;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        overflow-y: auto;
    }
    
    /* Main content area */
    .fi-main {
        margin-left: 20rem;
        flex: 1;
        width: calc(100% - 20rem);
    }
    
    /* Dark mode support */
    .dark .fi-sidebar {
        background: #111827;
        border-right-color: #374151;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 1024px) {
        .fi-sidebar {
            transform: translateX(-100%);
            z-index: 50;
        }
        
        .fi-sidebar.fi-sidebar-open {
            transform: translateX(0);
        }
        
        .fi-main {
            margin-left: 0;
            width: 100%;
        }
    }
</style>

{{-- TEMPORARY: Alpine.js script removed for demo to prevent conflicts --}}
{{-- Alpine is already initialized by Livewire/Filament --}}

{{-- Load additional fixes for admin portal --}}
@php
    $appPortal = session('current_portal', 'admin');
@endphp

@if($appPortal === 'admin' && request()->routeIs('filament.admin.*'))
    {{-- Scripts removed - unified-ui-fix.js handles all UI fixes globally in base.blade.php --}}
@endif