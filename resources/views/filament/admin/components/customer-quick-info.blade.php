@php
    $appointmentCount = $customer->appointments()->count();
    $lastAppointment = $customer->appointments()->latest('starts_at')->first();
    $upcomingCount = $customer->appointments()
        ->where('starts_at', '>', now())
        ->whereIn('status', ['scheduled', 'confirmed'])
        ->count();
    $noShowRate = $appointmentCount > 0 
        ? round(($customer->no_show_count / $appointmentCount) * 100) 
        : 0;
@endphp

<div class="customer-quick-info bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
    <div class="flex items-start gap-4">
        <!-- Avatar -->
        <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-primary-500 text-white rounded-full flex items-center justify-center font-bold text-lg">
                {{ substr($customer->name, 0, 2) }}
            </div>
        </div>

        <!-- Customer Details -->
        <div class="flex-1">
            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</h4>
            <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <p class="flex items-center gap-2">
                    <x-heroicon-o-phone class="w-4 h-4" />
                    {{ $customer->phone }}
                </p>
                @if($customer->email)
                <p class="flex items-center gap-2">
                    <x-heroicon-o-envelope class="w-4 h-4" />
                    {{ $customer->email }}
                </p>
                @endif
            </div>
        </div>

        <!-- VIP Badge -->
        @if($customer->is_vip)
        <div class="flex-shrink-0">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                ⭐ VIP
            </span>
        </div>
        @endif
    </div>

    <!-- Stats Grid -->
    <div class="mt-4 grid grid-cols-4 gap-3">
        <div class="text-center">
            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $appointmentCount }}</div>
            <div class="text-xs text-gray-500">Total Visits</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-semibold text-primary-600">{{ $upcomingCount }}</div>
            <div class="text-xs text-gray-500">Upcoming</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-semibold {{ $noShowRate > 20 ? 'text-danger-600' : 'text-success-600' }}">
                {{ $noShowRate }}%
            </div>
            <div class="text-xs text-gray-500">No-Show Rate</div>
        </div>
        <div class="text-center">
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                @if($lastAppointment)
                    {{ $lastAppointment->starts_at->diffInDays(now()) }}d
                @else
                    -
                @endif
            </div>
            <div class="text-xs text-gray-500">Since Last</div>
        </div>
    </div>

    <!-- Warnings/Alerts -->
    @if($noShowRate > 20)
    <div class="mt-3 p-2 bg-warning-100 dark:bg-warning-900/20 border border-warning-300 dark:border-warning-700 rounded-lg">
        <p class="text-xs text-warning-800 dark:text-warning-300 flex items-center gap-1">
            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
            High no-show rate detected. Consider confirmation reminders.
        </p>
    </div>
    @endif

    @if($customer->notes)
    <div class="mt-3 p-2 bg-info-100 dark:bg-info-900/20 border border-info-300 dark:border-info-700 rounded-lg">
        <p class="text-xs text-info-800 dark:text-info-300">
            <strong>Note:</strong> {{ Str::limit($customer->notes, 100) }}
        </p>
    </div>
    @endif

    <!-- Last Appointment Info -->
    @if($lastAppointment)
    <div class="mt-3 pt-3 border-t dark:border-gray-700">
        <p class="text-xs text-gray-500">Last appointment:</p>
        <p class="text-sm font-medium text-gray-900 dark:text-white">
            {{ $lastAppointment->service->name ?? 'Service' }} - {{ $lastAppointment->starts_at->format('M j, Y') }}
        </p>
    </div>
    @endif
</div>