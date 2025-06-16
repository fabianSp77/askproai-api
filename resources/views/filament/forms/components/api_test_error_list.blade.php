@if (!empty($errors))
    <ul class="text-sm text-red-600 space-y-1">
        @foreach ($errors as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@else
    <span class="text-sm text-gray-400">Keine Fehler vorhanden.</span>
@endif
