{{-- Initialize Stripe Menu System - Hide on login page --}}
@if(!str_contains(request()->path(), 'login') && !str_contains(request()->path(), 'register') && !str_contains(request()->path(), 'password'))
    @include('stripe-menu-standalone')
@endif