@php
$attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp

<!doctype html>
<html lang="en" class="{{ $attributes->get('class', '') }} dark">
  <head>
    @{{  partial "header" .  }}
  </head>
  @{{  block "body_override" .  }}
    <body class="{{ $attributes->get('class', '') }} @{{  if .Params.white_bg  }}bg-gray-50 dark:bg-gray-900@{{  else  }}bg-gray-50 dark:bg-gray-900@{{  end  }} antialiased">
      @{{  partial "skippy" .  }}
      @{{  block "main" .  }}
      @{{  end  }}
      @{{  partial "scripts" .  }}
    </body>
  @{{  end  }}
</html>
