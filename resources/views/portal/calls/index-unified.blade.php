@extends('portal.layouts.unified')

@section('page-title', 'Anrufe')

@section('content')
<div class="p-6">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Suche</label>
                <input type="text" name="search" placeholder="Telefonnummer oder Name..." 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Alle</option>
                    <option value="new">Neu</option>
                    <option value="in_progress">In Bearbeitung</option>
                    <option value="completed">Abgeschlossen</option>
                    <option value="requires_action">Aktion erforderlich</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zeitraum</label>
                <select name="period" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Alle</option>
                    <option value="today">Heute</option>
                    <option value="week">Diese Woche</option>
                    <option value="month">Dieser Monat</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                    <i class="fas fa-search mr-2"></i>
                    Filtern
                </button>
            </div>
        </div>
    </div>

    <!-- Calls Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum & Zeit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefonnummer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kunde</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dauer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filiale</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($calls as $call)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $call->created_at->format('d.m.Y') }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $call->created_at->format('H:i:s') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $call->phone_number }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($call->customer)
                            <div class="text-sm font-medium text-gray-900">
                                {{ $call->customer->name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $call->customer->email }}
                            </div>
                            @else
                            <span class="text-sm text-gray-500">Unbekannt</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ gmdate('i:s', $call->duration_sec ?? 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $status = $call->portal_status ?? $call->status ?? 'new';
                                $statusColors = [
                                    'new' => 'bg-blue-100 text-blue-800',
                                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'requires_action' => 'bg-red-100 text-red-800',
                                    'ended' => 'bg-gray-100 text-gray-800'
                                ];
                                $statusLabels = [
                                    'new' => 'Neu',
                                    'in_progress' => 'In Bearbeitung',
                                    'completed' => 'Abgeschlossen',
                                    'requires_action' => 'Aktion erforderlich',
                                    'ended' => 'Beendet'
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$status] ?? $status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $call->branch->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('business.calls.show', $call) }}" class="text-blue-600 hover:text-blue-900" title="Details anzeigen">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($call->recording_url)
                                <a href="{{ $call->recording_url }}" target="_blank" class="text-green-600 hover:text-green-900" title="Aufnahme anhören">
                                    <i class="fas fa-play-circle"></i>
                                </a>
                                @endif
                                <button onclick="updateCallStatus({{ $call->id }})" class="text-yellow-600 hover:text-yellow-900" title="Status aktualisieren">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-phone-slash text-4xl mb-2"></i>
                                <p>Keine Anrufe gefunden</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($calls->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $calls->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function updateCallStatus(id) {
    // Implement status update modal
    if (confirm('Status aktualisieren?')) {
        // Show status selection modal
        const newStatus = prompt('Neuer Status (new, in_progress, completed, requires_action):');
        if (newStatus) {
            fetch(`/business/api/calls/${id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ portal_status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }
    }
}

// Filter form handling
document.addEventListener('DOMContentLoaded', function() {
    const filters = document.querySelectorAll('input[name="search"], select[name="status"], select[name="period"]');
    const filterBtn = document.querySelector('button');
    
    filterBtn.addEventListener('click', function() {
        const params = new URLSearchParams();
        filters.forEach(filter => {
            if (filter.value) {
                params.append(filter.name, filter.value);
            }
        });
        window.location.href = '{{ route("business.calls.index") }}?' + params.toString();
    });
});
</script>
@endpush
@endsection