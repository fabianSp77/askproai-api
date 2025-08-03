@extends('portal.layouts.unified')

@section('page-title', 'Anruf Details')

@section('header-actions')
<a href="{{ route('business.calls.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200">
    <i class="fas fa-arrow-left mr-2"></i>
    Zurück zur Übersicht
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Call Details -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Anruf Details</h2>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Telefonnummer</p>
                        <p class="font-medium text-gray-900">{{ $call->phone_number }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Datum & Zeit</p>
                        <p class="font-medium text-gray-900">{{ $call->created_at->format('d.m.Y H:i:s') }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Dauer</p>
                        <p class="font-medium text-gray-900">{{ gmdate('i:s', $call->duration_sec ?? 0) }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
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
                    </div>
                    
                    @if($call->branch)
                    <div>
                        <p class="text-sm text-gray-500">Filiale</p>
                        <p class="font-medium text-gray-900">{{ $call->branch->name }}</p>
                    </div>
                    @endif
                    
                    @if($call->staff)
                    <div>
                        <p class="text-sm text-gray-500">Bearbeiter</p>
                        <p class="font-medium text-gray-900">{{ $call->staff->name }}</p>
                    </div>
                    @endif
                </div>
                
                @if($call->summary || $call->dynamic_variables)
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Zusammenfassung</h3>
                    @if($call->summary)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $call->summary }}</p>
                    </div>
                    @endif
                    
                    @if($call->dynamic_variables)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Extrahierte Informationen</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <pre class="text-sm text-gray-600">{{ json_encode($call->dynamic_variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
                
                @if($call->recording_url)
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Aufnahme</h3>
                    <audio controls class="w-full">
                        <source src="{{ $call->recording_url }}" type="audio/mpeg">
                        Ihr Browser unterstützt keine Audio-Wiedergabe.
                    </audio>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Customer Info -->
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Kunde</h2>
                
                @if($call->customer)
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="font-medium text-gray-900">{{ $call->customer->name }}</p>
                    </div>
                    
                    @if($call->customer->email)
                    <div>
                        <p class="text-sm text-gray-500">E-Mail</p>
                        <p class="font-medium text-gray-900">{{ $call->customer->email }}</p>
                    </div>
                    @endif
                    
                    <div>
                        <p class="text-sm text-gray-500">Telefon</p>
                        <p class="font-medium text-gray-900">{{ $call->customer->phone }}</p>
                    </div>
                    
                    <div class="pt-4">
                        <a href="{{ route('business.customers.show', $call->customer) }}" class="text-blue-600 hover:text-blue-700 text-sm">
                            Kundenprofil anzeigen →
                        </a>
                    </div>
                </div>
                @else
                <p class="text-gray-500">Kein Kunde zugeordnet</p>
                <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200 text-sm">
                    Kunde zuordnen
                </button>
                @endif
            </div>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Aktionen</h3>
                <div class="space-y-2">
                    <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200 text-sm">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Termin erstellen
                    </button>
                    <button class="w-full bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition duration-200 text-sm">
                        <i class="fas fa-edit mr-2"></i>
                        Status ändern
                    </button>
                    @if($call->recording_url)
                    <a href="{{ $call->recording_url }}" download class="block w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition duration-200 text-sm text-center">
                        <i class="fas fa-download mr-2"></i>
                        Aufnahme herunterladen
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Optional: Implement status change modal
function changeStatus() {
    // Implementation here
}
</script>
@endpush
@endsection