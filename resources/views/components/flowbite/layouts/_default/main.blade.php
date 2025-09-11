{{-- Flowbite Component: main --}}
@props(['title' => '', 'description' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
{{ define "main" }}
  {{ if .Params.navigation }}{{ partial "navbar-main" . }}{{ end }}
  <main class="bg-gray-50 dark:bg-gray-900">
    {{ .Content }}
  </main>
  {{ if .Params.footer }}{{ partial "footer-main" . }}{{ end }}
{{ end }}

</div>
