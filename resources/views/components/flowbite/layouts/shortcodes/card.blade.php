{{-- Flowbite Component: card --}}
@props(['title' => '', 'description' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
<div class="{{ with .Get "class" }}{{ . }}{{ end }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6 xl:p-8">
  {{ .Inner }}
</div>

</div>
