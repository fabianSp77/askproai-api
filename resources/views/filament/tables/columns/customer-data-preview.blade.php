@if($getState())
    @php
        $data = is_string($getState()) ? json_decode($getState(), true) : $getState();
    @endphp
    
    @if($data && is_array($data) && count($data) > 0)
        <div class="space-y-1 text-xs">
            @foreach($data as $key => $value)
                @if($value && !is_array($value))
                    <div class="flex gap-1">
                        <span class="font-medium text-gray-600 dark:text-gray-400">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}:
                        </span>
                        <span class="text-gray-900 dark:text-gray-100">
                            {{ $value }}
                        </span>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <span class="text-gray-500 dark:text-gray-400 text-xs">Keine Daten</span>
    @endif
@else
    <span class="text-gray-500 dark:text-gray-400 text-xs">-</span>
@endif