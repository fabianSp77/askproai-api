@extends('portal.layouts.unified')

@section('page-title', 'Abrechnung')

@section('content')
<div id="billing-index-root" class="min-h-screen">
    <!-- React app will mount here -->
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-4 text-gray-600">Lade Abrechnungsdaten...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Load React app without problematic Alpine scripts -->
<script src="{{ asset('build/assets/portal-billing-DDGKifal.js') }}" type="module"></script>
@endpush