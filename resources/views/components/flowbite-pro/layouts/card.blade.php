@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

<div class="{{ $attributes->get('class', '') }} @{{  with .Get "class"  }}@{{  .  }}@{{  end  }} rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800 sm:p-6 xl:p-8">
  @{{  .Inner  }}
</div>
