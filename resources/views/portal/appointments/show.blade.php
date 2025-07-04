@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Termin Details</h2>
                    <a href="{{ route('business.appointments.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                        ← Zurück zur Übersicht
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Appointment Info -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Termin Informationen</h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Datum & Zeit</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $appointment->starts_at->format('d.m.Y H:i') }} - 
                                    {{ $appointment->ends_at->format('H:i') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $appointment->status == 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $appointment->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $appointment->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($appointment->status) }}
                                    </span>
                                </dd>
                            </div>
                            @if($appointment->service)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Service</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $appointment->service->name }}</dd>
                            </div>
                            @endif
                            @if($appointment->branch)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Filiale</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $appointment->branch->name }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                    
                    <!-- Customer & Staff Info -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Kunde & Mitarbeiter</h3>
                        <dl class="space-y-2">
                            @if($appointment->customer)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Kunde</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $appointment->customer->name }}<br>
                                    <span class="text-gray-500">{{ $appointment->customer->phone }}</span>
                                </dd>
                            </div>
                            @endif
                            @if($appointment->staff)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Mitarbeiter</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $appointment->staff->name }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
                
                @if($appointment->notes)
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Notizen</h3>
                    <p class="text-sm text-gray-600">{{ $appointment->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection