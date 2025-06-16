@php
    // $errors kann alles sein: null, leeres Feld, String oder Array
    try {
        if (is_string($errors)) {
            $errors = json_decode($errors, true) ?: [];
        }
        if (!is_array($errors)) {
            $errors = [];
        }
    } catch (\Throwable $e) {
        $errors = [];
    }
@endphp

@if (!empty($errors))
    @foreach ($errors as $type => $list)
        @if(is_array($list))
            <div class="mb-4">
                <div class="font-semibold text-blue-600">{{ ucfirst($type) }} Fehler</div>
                <ul class="text-xs text-red-600">
                    @foreach ($list as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach
@else
    <span class="text-xs text-gray-400">Keine Fehler vorhanden.</span>
@endif
