<div>
    <h3 style="font-weight:bold;">Letzte 10 API-Fehlermeldungen</h3>
    @php
        $errors = $getRecord()?->api_test_errors ?? [];
        if (is_string($errors)) $errors = json_decode($errors, true) ?? [];
    @endphp
    @if (count($errors))
        <ul style="margin-left:20px;">
            @foreach ($errors as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    @else
        <p>Keine Fehler gespeichert.</p>
    @endif
</div>
