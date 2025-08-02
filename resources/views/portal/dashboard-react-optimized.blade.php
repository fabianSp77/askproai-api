@extends('portal.layouts.optimized')

@section('page-title', 'Dashboard')

@section('content')
<div id="portal-app" class="min-h-screen">
    {{-- Loading state (will be replaced by React) --}}
    <div class="app-loading">
        <div class="app-loading-spinner"></div>
        <p class="mt-4 text-gray-600">Lade Dashboard...</p>
    </div>
</div>
@endsection

{{-- No need for additional scripts - the portal bundle handles everything --}}