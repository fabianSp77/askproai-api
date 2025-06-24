<x-filament-panels::page class="fi-company-integration-portal">
    <div class="bg-yellow-100 p-4 rounded mb-4">
        <h2 class="font-bold">Debug Information:</h2>
        <ul>
            <li>Companies Count: {{ count($companies ?? []) }}</li>
            <li>Selected Company: {{ $selectedCompanyId ?? 'none' }}</li>
            <li>User: {{ auth()->user()->email ?? 'not logged in' }}</li>
            <li>Component Class: {{ get_class($this) }}</li>
        </ul>
    </div>

    <div class="space-y-6">
        {{-- Simple Company List --}}
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-lg font-bold mb-4">Companies:</h2>
            @if(count($companies ?? []) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($companies as $company)
                        <button 
                            wire:click="selectCompany({{ $company['id'] }})"
                            class="p-4 border rounded hover:bg-gray-50 text-left"
                        >
                            <div class="font-semibold">{{ $company['name'] }}</div>
                            <div class="text-sm text-gray-500">ID: {{ $company['id'] }}</div>
                        </button>
                    @endforeach
                </div>
            @else
                <p>No companies found.</p>
            @endif
        </div>
        
        {{-- Show selected company info --}}
        @if($selectedCompany)
            <div class="bg-white p-6 rounded shadow">
                <h2 class="text-lg font-bold mb-4">Selected Company: {{ $selectedCompany->name }}</h2>
                <p>Company ID: {{ $selectedCompany->id }}</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>