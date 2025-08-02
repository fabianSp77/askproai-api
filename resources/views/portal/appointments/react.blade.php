@extends('portal.layouts.unified')

@section('page-title', 'Termine')

@section('content')
<div id="appointments-index-root" class="min-h-screen">
    <!-- React app will mount here -->
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Lade Termine...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Load React app -->
<script src="{{ asset('build/assets/portal-appointments-CY77qzFr.js') }}" type="module"></script>
@endpush