@extends('portal.layouts.app')

@section('content')
<div id="settings-index-root"></div>
@endsection

@push('scripts')
@vite(['resources/js/portal-settings.jsx'])
@endpush