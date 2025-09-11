@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

@{{  define "body_override"  }}
  <body class="{{ $attributes->get('class', '') }} d-flex flex-column min-vh-100"></body>
@{{  end  }}
@{{  define "main"  }}
  <main class="{{ $attributes->get('class', '') }} my-auto p-5" id="content">
    @{{  .Content  }}
  </main>
@{{  end  }}
