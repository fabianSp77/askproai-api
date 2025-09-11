@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

<!-- <div class="{{ $attributes->get('class', '') }} overflow-hidden skippy visually-hidden-focusable">
  <div class="{{ $attributes->get('class', '') }} containe">
    <a class="{{ $attributes->get('class', '') }} p-2 m-1 d-inline-flex" href="#content">Skip to main content</a>
    @{{  if (eq .Page.Layout "docs") - }}
  <a class="{{ $attributes->get('class', '') }} d-none d-md-inline-flex m-1 p-2" href="#bd-docs-nav">Skip to docs navigation</a>
@{{ - end  }}
  </div>
</div> -->
