@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
            <div class="text-sm text-gray-600 mt-1 space-y-1">
                @if(isset($branchId) && $branchId && isset($branches))
                    @php
                        $selectedBranch = $branches->firstWhere('id', $branchId);
                    @endphp
                    @if($selectedBranch)
                        <p>Filiale: <strong>{{ $selectedBranch->name }}</strong></p>
                    @endif
                @else
                    <p>Filiale: <strong>Alle Filialen</strong></p>
                @endif
                
                @if(isset($phoneNumber) && $phoneNumber && isset($phoneNumbers))
                    @php
                        $selectedPhone = $phoneNumbers->firstWhere('number', $phoneNumber);
                    @endphp
                    @if($selectedPhone)
                        <p>Telefonnummer: <strong>{{ $selectedPhone->number }}</strong>
                        @if($selectedPhone->description)
                            ({{ $selectedPhone->description }})
                        @endif
                        </p>
                    @endif
                @else
                    <p>Telefonnummer: <strong>Alle Nummern</strong></p>
                @endif
            </div>
        </div>
        
        <!-- Date Filter -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('business.analytics.index') }}" class="flex flex-wrap items-end gap-4">
                    @if(isset($branches) && $branches->count() > 1)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filiale</label>
                        <select name="branch_id" id="branch_id" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updatePhoneNumbers()">
                            <option value="">Alle Filialen</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    
                    @if(isset($phoneNumbers) && $phoneNumbers->count() > 0)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefonnummer</label>
                        <select name="phone_number" id="phone_number" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Alle Nummern</option>
                            @foreach($phoneNumbers as $phone)
                                <option value="{{ $phone->number }}" 
                                        data-branch="{{ $phone->branch_id }}"
                                        {{ $phoneNumber == $phone->number ? 'selected' : '' }}>
                                    {{ $phone->number }}
                                    @if($phone->description)
                                        - {{ $phone->description }}
                                    @endif
                                    @if($phone->branch)
                                        ({{ $phone->branch->name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Von</label>
                        <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" 
                               class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bis</label>
                        <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" 
                               class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Filter anwenden
                    </button>
                </form>
            </div>
        </div>

        <!-- Call Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-600">Gesamtanrufe</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ $callStats['total_calls'] }}</div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-600">Gesamtdauer</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        @if($callStats['total_duration'] > 0)
                            {{ gmdate('H:i:s', $callStats['total_duration']) }}
                        @else
                            00:00:00
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-600">Ø Dauer</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        @if($callStats['average_duration'] > 0)
                            {{ gmdate('i:s', $callStats['average_duration']) }}
                        @else
                            00:00
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-600">Gesamtkosten</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        @if($callStats['total_duration'] > 0)
                            {{ number_format(($callStats['total_duration'] / 60) * 0.42, 2, ',', '.') }} €
                        @else
                            0,00 €
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Hourly Distribution -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Anrufe nach Uhrzeit</h3>
                <div class="overflow-x-auto">
                    <div class="flex items-end space-x-2" style="height: 200px;">
                        @foreach(range(0, 23) as $hour)
                            @php
                                $count = $hourlyDistribution->firstWhere('hour', $hour)->count ?? 0;
                                $maxCount = $hourlyDistribution->max('count') ?: 1;
                                $height = ($count / $maxCount) * 100;
                            @endphp
                            <div class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-blue-500 rounded-t" style="height: {{ $height }}%;"></div>
                                <span class="text-xs text-gray-600 mt-1">{{ $hour }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Kunden</h3>
                @if($topCustomers->isEmpty())
                    <p class="text-gray-500">Keine Daten vorhanden.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Kunde
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Telefon
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Anrufe
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($topCustomers as $customer)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $customer->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $customer->phone }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $customer->call_count }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function updatePhoneNumbers() {
    const branchSelect = document.getElementById('branch_id');
    const phoneSelect = document.getElementById('phone_number');
    
    if (!phoneSelect) return;
    
    const selectedBranch = branchSelect ? branchSelect.value : '';
    const phoneOptions = phoneSelect.querySelectorAll('option');
    
    // Show/hide phone numbers based on selected branch
    phoneOptions.forEach(option => {
        if (option.value === '') {
            // Always show "All Numbers" option
            option.style.display = '';
        } else {
            const phoneBranch = option.getAttribute('data-branch');
            if (!selectedBranch || phoneBranch === selectedBranch) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    // Reset phone selection if current selection is hidden
    const currentPhone = phoneSelect.value;
    if (currentPhone) {
        const currentOption = phoneSelect.querySelector(`option[value="${currentPhone}"]`);
        if (currentOption && currentOption.style.display === 'none') {
            phoneSelect.value = '';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePhoneNumbers();
});
</script>
@endsection