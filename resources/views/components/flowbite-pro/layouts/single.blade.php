@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

@{{  define "main"  }}
  <header class="{{ $attributes->get('class', '') }} border-bottom py-5">
    <div class="{{ $attributes->get('class', '') }} pt-md-1 pb-md-4 container">
      <h1 class="{{ $attributes->get('class', '') }} bd-title mt-0">@{{  .Title | markdownify  }}</h1>
      <p class="{{ $attributes->get('class', '') }} bd-lead">@{{  .Page.Params.Description | markdownify  }}</p>
      @{{  if eq .Title "Examples"  }}
        <div class="{{ $attributes->get('class', '') }} d-flex flex-column flex-sm-row">
          <a href="@{{  .Site.Params.download.dist_examples  }}" class="{{ $attributes->get('class', '') }} btn btn-lg btn-bd-primary" onclick="ga('send', 'event', 'Examples', 'Hero', 'Download Examples');">Download examples</a>
        </div>
      @{{  end  }}
    </div>
  </header>

  <main class="{{ $attributes->get('class', '') }} bd-content order-1 py-5" id="content">
    <div class="{{ $attributes->get('class', '') }} container">
      @{{  .Content  }}
    </div>
  </main>
@{{  end  }}
