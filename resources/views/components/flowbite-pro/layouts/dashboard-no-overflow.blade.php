@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

@{{  define "main"  }}
  @{{  partial "navbar-dashboard" .  }}
  <div class="{{ $attributes->get('class', '') }} flex overflow-hidden bg-gray-50 pt-[62px] dark:bg-gray-900">
    @{{  partial "sidebar" .  }}
    <div id="main-content" class="{{ $attributes->get('class', '') }} relative h-full w-full bg-gray-50 dark:bg-gray-900 lg:ms-64">
      <main>
        @{{  .Content  }}
      </main>
      @{{  if .Params.footer  }}@{{  partial "footer-dashboard" .  }}@{{  end  }}
    </div>
  </div>
@{{  end  }}
