@extends('portal.layouts.app')

@section('content')
<div id="appointments-index-root"></div>
@endsection

@push('scripts')
@vite('resources/js/portal-appointments.jsx')
@endpush