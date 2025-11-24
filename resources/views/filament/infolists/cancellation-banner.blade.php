{{--
    Cancellation Banner for Call Detail View
    Prominent warning-style banner with key info and navigation
--}}

<div class="rounded-lg border-2 border-orange-500 bg-orange-50 dark:bg-orange-950/20 p-6 space-y-4">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <div class="flex-shrink-0">
            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-orange-600 dark:text-orange-400" />
        </div>
        <div>
            <h3 class="text-xl font-bold text-orange-900 dark:text-orange-100">
                Appointment Cancelled
            </h3>
            <p class="text-sm text-orange-700 dark:text-orange-300">
                This call resulted in the cancellation of a scheduled appointment.
            </p>
        </div>
    </div>

    {{-- Original Appointment Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Original Appointment:</span>
            <div class="mt-1 text-gray-900 dark:text-gray-100">
                <div class="line-through opacity-75">
                    {{ $appointment->service->name }}
                </div>
                <div class="line-through opacity-75">
                    {{ $appointment->scheduled_at->format('l, F j, Y \a\t g:i A') }}
                </div>
            </div>
        </div>

        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Cancellation:</span>
            <div class="mt-1 text-gray-900 dark:text-gray-100">
                <div>{{ $cancellation->cancelled_at->format('M j, Y \a\t g:i A') }}</div>
                @if($cancellation->cancelled_by_type)
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        By: {{ ucfirst($cancellation->cancelled_by_type) }}
                        @if($cancellation->cancelled_by_name)
                            ({{ $cancellation->cancelled_by_name }})
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Additional Metadata (Fee, Reason) --}}
    <div class="flex flex-wrap gap-6 text-sm">
        @if($cancellation->cancellation_fee > 0)
            <div class="flex items-center gap-2">
                <x-heroicon-o-currency-euro class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Cancellation Fee:</span>
                    <span class="ml-1 font-bold text-orange-700 dark:text-orange-300">
                        {{ number_format($cancellation->cancellation_fee, 2) }} €
                    </span>
                </div>
            </div>
        @endif

        @if($cancellation->reason)
            <div class="flex items-center gap-2">
                <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Reason:</span>
                    <span class="ml-1 text-gray-900 dark:text-gray-100">
                        {{ $cancellation->reason }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    {{-- Navigation Links --}}
    @if($originalCall && $originalCall->id !== $appointment->call_id)
        <div class="pt-4 border-t border-orange-200 dark:border-orange-800">
            <a
                href="{{ route('filament.admin.resources.calls.view', $originalCall->id) }}"
                class="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold transition-colors"
            >
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                View original booking call (#{{ $originalCall->id }})
            </a>
        </div>
    @endif
</div>
