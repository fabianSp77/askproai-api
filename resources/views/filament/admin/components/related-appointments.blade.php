@php
    $call = $getRecord();
    $customer = $call->customer;
    
    // Get linked appointment
    $linkedAppointment = $call->appointment;
    
    // Get other appointments
    $otherAppointments = $customer ? $customer->appointments()
        ->when($linkedAppointment, function($q) use ($linkedAppointment) {
            $q->where('id', '!=', $linkedAppointment->id);
        })
        ->latest()
        ->limit(5)
        ->get() : collect();
    
    $totalAppointments = $customer ? $customer->appointments()->count() : 0;
@endphp

<div class="related-appointments">
    @if($linkedAppointment)
        <!-- Linked Appointment -->
        <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <h4 class="text-sm font-medium text-green-800 dark:text-green-300 mb-2 flex items-center gap-1">
                <x-heroicon-o-link class="w-4 h-4" />
                Appointment from this call
            </h4>
            <a href="{{ route('filament.admin.resources.ultimate-appointments.view', $linkedAppointment) }}" 
               class="block hover:bg-green-100 dark:hover:bg-green-900/30 rounded p-2 -m-2 transition">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium">{{ $linkedAppointment->service->name ?? 'Service' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $linkedAppointment->starts_at->format('M j, Y - g:i A') }}
                        </p>
                        @if($linkedAppointment->staff)
                            <p class="text-xs text-gray-500 mt-1">
                                With {{ $linkedAppointment->staff->name }}
                            </p>
                        @endif
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        {{ $linkedAppointment->status === 'confirmed' ? 'bg-green-100 text-green-800' : 
                           ($linkedAppointment->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                            'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst($linkedAppointment->status) }}
                    </span>
                </div>
            </a>
        </div>
    @endif

    @if($customer)
        <!-- Other Appointments -->
        @if($otherAppointments->count() > 0)
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Other Appointments</h4>
            <div class="space-y-2">
                @foreach($otherAppointments as $appointment)
                    <a href="{{ route('filament.admin.resources.ultimate-appointments.view', $appointment) }}" 
                       class="block p-2 bg-gray-50 dark:bg-gray-800 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium">{{ $appointment->service->name ?? 'Service' }}</p>
                                <p class="text-xs text-gray-500">{{ $appointment->starts_at->format('M j, Y - g:i A') }}</p>
                            </div>
                            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                        </div>
                    </a>
                @endforeach
            </div>

            @if($totalAppointments > 5)
                <div class="mt-3 text-center">
                    <a href="{{ route('filament.admin.resources.ultimate-customers.view', $customer) }}#appointments" 
                       class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        View all {{ $totalAppointments }} appointments →
                    </a>
                </div>
            @endif
        @elseif(!$linkedAppointment)
            <p class="text-sm text-gray-500 text-center py-4">No appointments scheduled</p>
            <div class="mt-2">
                <button type="button" 
                        onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'create-appointment-modal' } }))"
                        class="w-full px-3 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition text-sm font-medium">
                    <x-heroicon-o-plus class="w-4 h-4 inline mr-1" />
                    Schedule Appointment
                </button>
            </div>
        @endif
    @else
        <p class="text-sm text-gray-500 text-center py-4">No customer linked to this call</p>
    @endif
</div>