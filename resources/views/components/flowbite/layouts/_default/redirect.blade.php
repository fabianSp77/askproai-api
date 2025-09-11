{{-- Flowbite Component: redirect --}}
@props(['title' => '', 'description' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
{{ partial "redirect" (.Page.Params.redirect | absURL) }}

</div>
