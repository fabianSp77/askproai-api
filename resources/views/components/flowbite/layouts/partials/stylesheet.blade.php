{{-- Flowbite Component: stylesheet --}}
@props(['title' => '', 'description' => ''])
<div {{ $attributes->merge(['class' => '']) }}>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
  href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
  rel="stylesheet"
/>

<!-- <link
  rel="stylesheet"
  href="https://www.unpkg.com/flowbite-datepicker@1.3.2/dist/css/datepicker.min.css"
/> -->
<link rel="stylesheet" href="{{ .Site.BaseURL }}app.css" />

</div>
