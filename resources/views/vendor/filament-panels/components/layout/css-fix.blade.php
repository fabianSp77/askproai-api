{{-- Emergency CSS Fix for Filament Layout --}}
<link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
<link rel="stylesheet" href="{{ asset('css/filament-hotfix.css') }}">

<style>
    /* Emergency inline styles to ensure basic layout */
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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