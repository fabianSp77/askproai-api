@extends('portal.layouts.app')

@section('content')
<div id="app"></div>
@endsection

@push('scripts')
@vite(['resources/js/portal-dashboard-optimized.jsx'])
@endpush