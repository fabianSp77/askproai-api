@php
    $appointment = $getRecord();
    $customer = $appointment->customer;
    
    $appointments = $customer ? $customer->appointments()
        ->where('id', '!=', $appointment->id)
        ->orderBy('starts_at', 'desc')
        ->limit(5)
        ->get() : collect();
    
    $stats = $customer ? [
        'total' => $customer->appointments()->count(),
        'completed' => $customer->appointments()->where('status', 'completed')->count(),
        'cancelled' => $customer->appointments()->where('status', 'cancelled')->count(),
        'no_shows' => $customer->appointments()->where('status', 'no_show')->count(),
    ] : null;
@endphp

<div class="customer-appointment-history">
    @if($customer)
        <!-- Statistics -->
        <div class="grid grid-cols-4 gap-2 mb-4">
            <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-lg font-semibold text-primary-600">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500">Total</div>
            </div>
            <div class="text-center p-2 bg-success-50 dark:bg-success-900/20 rounded">
                <div class="text-lg font-semibold text-success-600">{{ $stats['completed'] }}</div>
                <div class="text-xs text-gray-500">Completed</div>
            </div>
            <div class="text-center p-2 bg-danger-50 dark:bg-danger-900/20 rounded">
                <div class="text-lg font-semibold text-danger-600">{{ $stats['cancelled'] }}</div>
                <div class="text-xs text-gray-500">Cancelled</div>
            </div>
            <div class="text-center p-2 bg-warning-50 dark:bg-warning-900/20 rounded">
                <div class="text-lg font-semibold text-warning-600">{{ $stats['no_shows'] }}</div>
                <div class="text-xs text-gray-500">No Shows</div>
            </div>
        </div>

        <!-- Recent Appointments -->
        @if($appointments->count() > 0)
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recent Appointments</h4>
            <div class="space-y-2">
                @foreach($appointments as $apt)
                    <a href="{{ route('filament.admin.resources.ultimate-appointments.view', $apt) }}" 
                       class="block p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-medium text-sm">{{ $apt->service->name ?? 'Service' }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $apt->starts_at->format('M j, Y - g:i A') }}
                                </p>
                                @if($apt->staff)
                                    <p class="text-xs text-gray-500">
                                        with {{ $apt->staff->name }}
                                    </p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                {{ match($apt->status) {
                                    'completed' => 'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300',
                                    'confirmed' => 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300',
                                    'scheduled' => 'bg-info-100 text-info-800 dark:bg-info-900/30 dark:text-info-300',
                                    'cancelled' => 'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-300',
                                    'no_show' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                } }}">
                                {{ ucfirst($apt->status) }}
                            </span>
                        </div>
                        
                        @if($apt->notes)
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">
                                {{ $apt->notes }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>

            @if($stats['total'] > 5)
                <div class="mt-4 text-center">
                    <a href="{{ route('filament.admin.resources.ultimate-customers.view', $customer) }}#appointments" 
                       class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        View all {{ $stats['total'] }} appointments →
                    </a>
                </div>
            @endif
        @else
            <div class="text-center py-4">
                <x-heroicon-o-calendar class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                <p class="text-sm text-gray-500">No other appointments found</p>
            </div>
        @endif

        <!-- Quick Actions -->
        <div class="mt-4 pt-4 border-t dark:border-gray-700">
            <button type="button" 
                    onclick="window.location.href='{{ route('filament.admin.resources.ultimate-appointments.create') }}?customer_id={{ $customer->id }}'"
                    class="w-full px-3 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition text-sm font-medium text-center">
                <x-heroicon-o-plus class="w-4 h-4 inline mr-1" />
                Book New Appointment
            </button>
        </div>
    @else
        <p class="text-sm text-gray-500 text-center py-4">
            No customer linked to this appointment
        </p>
    @endif
</div>