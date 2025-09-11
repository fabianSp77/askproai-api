{{-- Flowbite Component: scripts --}}
@props(['title' => '', 'description' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
<script src="{{ .Site.BaseURL }}app.bundle.js"></script>

</div>
