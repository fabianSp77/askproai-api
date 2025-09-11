{{-- Flowbite Component: alias --}}
@props(['title' => '', 'description' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
{{ partial "redirect" .Permalink }}

</div>
