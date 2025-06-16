<div>
    <h3 class="font-bold mb-2">Letzte API-Fehlermeldungen</h3>
    <ul class="text-sm text-red-600 space-y-1">
        @foreach(($getRecord()?->api_test_errors ?? []) as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
