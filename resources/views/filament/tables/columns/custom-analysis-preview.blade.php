@if($getState())
    @php
        $data = is_string($getState()) ? json_decode($getState(), true) : $getState();
    @endphp
    
    @if($data && is_array($data) && count($data) > 0)
        <div class="space-y-1 text-xs">
            @foreach($data as $key => $value)
                @if($value)
                    <div class="flex flex-col gap-0.5">
                        <span class="font-medium text-gray-600 dark:text-gray-400">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}:
                        </span>
                        @if(is_array($value))
                            <span class="text-gray-900 dark:text-gray-100 pl-2">
                                {{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                            </span>
                        @else
                            <span class="text-gray-900 dark:text-gray-100 pl-2">
                                {{ $value }}
                            </span>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <span class="text-gray-500 dark:text-gray-400 text-xs">Keine Analysedaten</span>
    @endif
@else
    <span class="text-gray-500 dark:text-gray-400 text-xs">-</span>
@endif