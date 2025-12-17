{{--
    Tooltip for Cancelled Appointment in List View
    Shows key cancellation metadata with link to cancellation call
--}}

<div class="space-y-2 text-xs max-w-xs">
    {{-- Header --}}
    <div class="font-semibold text-orange-600 dark:text-orange-400 flex items-center gap-1">
        <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
        <span>Appointment Cancelled</span>
    </div>

    {{-- Original Appointment Info --}}
    <div class="space-y-1 text-gray-700 dark:text-gray-300">
        <div>
            <span class="font-medium">Original:</span>
            <span class="line-through">{{ $appointment->scheduled_at->format('M j, Y H:i') }}</span>
        </div>
        <div>
            <span class="font-medium">Service:</span>
            {{ $appointment->service->name }}
        </div>
    </div>

    {{-- Cancellation Details --}}
    <div class="pt-2 border-t border-gray-200 dark:border-gray-700 space-y-1">
        <div>
            <span class="font-medium">Cancelled:</span>
            {{ $cancellation->cancelled_at->format('M j, Y H:i') }}
        </div>

        @if($cancellation->cancelled_by_type)
            <div>
                <span class="font-medium">By:</span>
                {{ ucfirst($cancellation->cancelled_by_type) }}
                @if($cancellation->cancelled_by_name)
                    ({{ $cancellation->cancelled_by_name }})
                @endif
            </div>
        @endif

        @if($cancellation->cancellation_fee > 0)
            <div class="text-orange-600 dark:text-orange-400">
                <span class="font-medium">Fee:</span>
                {{ number_format($cancellation->cancellation_fee, 2) }} €
            </div>
        @endif

        @if($cancellation->reason)
            <div>
                <span class="font-medium">Reason:</span>
                {{ Str::limit($cancellation->reason, 50) }}
            </div>
        @endif
    </div>

    {{-- Navigation Link --}}
    @if($cancellation->call_id && $cancellation->call_id !== $appointment->call_id)
        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
            <a
                href="{{ route('filament.admin.resources.calls.view', $cancellation->call_id) }}"
                class="text-blue-600 dark:text-blue-400 hover:underline font-medium flex items-center gap-1"
                onclick="event.stopPropagation()"
            >
                <x-heroicon-o-arrow-right class="w-3 h-3" />
                View cancellation call
            </a>
        </div>
    @endif
</div>
