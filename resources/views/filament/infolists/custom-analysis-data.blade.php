@php
    $data = $getState();
    if (is_string($data)) {
        $data = json_decode($data, true);
    }
@endphp

@if($data && is_array($data) && count($data) > 0)
    <div class="space-y-3">
        @foreach($data as $key => $value)
            @if($value)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ str_replace('_', ' ', ucfirst($key)) }}
                    </h4>
                    <div class="text-sm text-gray-900 dark:text-gray-100">
                        @if(is_array($value))
                            @if(count($value) > 0)
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($value as $item)
                                        <li>{{ is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : $item }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-gray-500">Leer</span>
                            @endif
                        @elseif(is_bool($value))
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $value ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $value ? 'Ja' : 'Nein' }}
                            </span>
                        @else
                            {{ $value }}
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400 italic">Keine benutzerdefinierten Analysedaten vorhanden</p>
@endif