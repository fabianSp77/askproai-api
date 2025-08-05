@extends('portal.layouts.app')

@section('title', 'Meine Termine')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Meine Termine</h1>
            <p class="mt-2 text-gray-600">Übersicht über alle Ihre Termine</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" action="{{ route('portal.appointments') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Alle</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Geplant</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Bestätigt</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Abgeschlossen</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Abgesagt</option>
                    </select>
                </div>
                
                <div class="flex-1 min-w-[200px]">
                    <label for="from" class="block text-sm font-medium text-gray-700 mb-1">Von</label>
                    <input type="date" name="from" id="from" value="{{ request('from') }}" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex-1 min-w-[200px]">
                    <label for="to" class="block text-sm font-medium text-gray-700 mb-1">Bis</label>
                    <input type="date" name="to" id="to" value="{{ request('to') }}" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Filtern
                    </button>
                    <a href="{{ route('portal.appointments') }}" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Zurücksetzen
                    </a>
                </div>
            </form>
        </div>

        <!-- Appointments List -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            @if($appointments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Datum & Zeit
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Service
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mitarbeiter
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Filiale
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aktionen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($appointments as $appointment)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $appointment->starts_at ? $appointment->starts_at->format('d.m.Y') : 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $appointment->starts_at ? $appointment->starts_at->format('H:i') : '' }}
                                            @if($appointment->ends_at)
                                                - {{ $appointment->ends_at->format('H:i') }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $appointment->service->name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $appointment->staff->name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $appointment->branch->name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'scheduled' => 'bg-blue-100 text-blue-800',
                                                'confirmed' => 'bg-green-100 text-green-800',
                                                'completed' => 'bg-gray-100 text-gray-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'no_show' => 'bg-yellow-100 text-yellow-800',
                                            ];
                                            $statusLabels = [
                                                'scheduled' => 'Geplant',
                                                'confirmed' => 'Bestätigt',
                                                'completed' => 'Abgeschlossen',
                                                'cancelled' => 'Abgesagt',
                                                'no_show' => 'Nicht erschienen',
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$appointment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabels[$appointment->status] ?? $appointment->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('portal.appointments.show', $appointment) }}" class="text-blue-600 hover:text-blue-900">
                                            Details anzeigen
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $appointments->withQueryString()->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Termine gefunden</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request()->hasAny(['status', 'from', 'to']))
                            Versuchen Sie, Ihre Filter anzupassen.
                        @else
                            Sie haben noch keine Termine.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection