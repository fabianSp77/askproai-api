@extends('portal.layouts.app')

@section('content')
<div id="customers-index-root"></div>
@endsection

@push('scripts')
@vite('resources/js/portal-customers.jsx')
@endpush