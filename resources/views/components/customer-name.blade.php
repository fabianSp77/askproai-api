@props(['call'])

@if($call->extracted_name || $call->customer)
    <div>
        <div class="text-sm text-gray-900 font-medium">
            {{ $call->extracted_name ?? $call->customer->name }}
        </div>
        @if($call->extracted_email || $call->customer?->email)
            <div class="text-xs text-gray-500">
                {{ $call->extracted_email ?? $call->customer->email }}
            </div>
        @endif
    </div>
@else
    <span class="text-sm text-gray-500">Unbekannt</span>
@endif