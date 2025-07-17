@extends('portal.layouts.app')

@section('content')
<div id="analytics-index-root"></div>
@endsection

@push('scripts')
@vite(['resources/js/portal-analytics.jsx'])
@endpush