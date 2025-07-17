@extends('portal.layouts.app')

@section('content')
<div id="team-index-root"></div>
@endsection

@push('scripts')
@vite(['resources/js/portal-team.jsx'])
@endpush