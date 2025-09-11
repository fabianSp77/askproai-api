{{-- Stripe Menu Standalone --}}
@php
    $navigationService = app(\App\Services\NavigationService::class);
    $navigation = $navigationService->getNavigation();
    $user = Auth::user();
@endphp

{{-- Load Assets --}}
@vite(['resources/css/stripe-menu.css', 'resources/js/stripe-menu.js'])

{{-- Desktop Navigation Bar --}}
<nav class="stripe-menu">
    <div class="stripe-menu-container">
        {{-- Logo --}}
        <a href="{{ url('/admin') }}" class="stripe-menu-logo">
            <span class="text-xl font-bold">{{ config('app.name', 'AskProAI') }}</span>
        </a>

        {{-- Desktop Navigation Items --}}
        <nav class="stripe-menu-nav">
            @foreach($navigation['main'] as $item)
                <a href="{{ $item['url'] }}" class="stripe-menu-item">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Actions (Right Side) --}}
        <div class="stripe-menu-actions">
            {{-- Mobile Menu Toggle --}}
            <div class="stripe-hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </div>
</nav>

{{-- Mega Menu (Hidden by default) --}}
<div class="stripe-mega-menu" style="display: none;">
    <!-- Mega menu content will be populated by JavaScript -->
</div>

{{-- Mobile Menu --}}
<aside class="stripe-mobile-menu" style="transform: translateX(-100%);">
    <div class="stripe-mobile-menu-header">
        <a href="{{ url('/admin') }}">
            <span class="text-xl font-bold">{{ config('app.name', 'AskProAI') }}</span>
        </a>
    </div>

    <nav class="stripe-mobile-menu-nav">
        {{-- Primary Navigation --}}
        <div class="mb-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Main</h4>
            @foreach($navigation['mobile']['primary'] as $item)
                <a href="{{ $item['url'] }}" class="stripe-mobile-menu-item">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Secondary Navigation --}}
        <div class="mb-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">More</h4>
            @foreach($navigation['mobile']['secondary'] as $item)
                <a href="{{ $item['url'] }}" class="stripe-mobile-menu-item">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</aside>

{{-- Mobile Menu Overlay --}}
<div class="stripe-menu-overlay" style="display: none;"></div>

{{-- Initialize JavaScript --}}
<script>
    // Navigation data for JavaScript
    window.navigationData = @json($navigation);
</script>