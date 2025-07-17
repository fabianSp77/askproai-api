@extends('portal.layouts.app')

@section('content')
<div id="billing-index-root"></div>
@endsection

@push('scripts')
@vite(['resources/js/portal-billing.jsx'])
@endpush