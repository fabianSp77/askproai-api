@props(['call', 'showTitle' => true])

@php
    $customerData = $call->metadata['customer_data'] ?? [];
    if (empty($customerData) && $call->customer_data_backup) {
        $customerData = is_string($call->customer_data_backup) 
            ? json_decode($call->customer_data_backup, true) 
            : $call->customer_data_backup;
    }
    $hasCustomerData = !empty($customerData);
@endphp

@if($hasCustomerData)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        @if($showTitle)
            <h4 class="text-sm font-semibold text-blue-900 mb-3 flex items-center">
                <x-heroicon-m-user-circle class="w-5 h-5 mr-2" />
                Erfasste Kundendaten
            </h4>
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Persönliche Daten --}}
            <div class="space-y-2">
                <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Persönliche Daten</h5>
                
                @if(isset($customerData['full_name']))
                    <div>
                        <span class="text-xs text-gray-600">Name:</span>
                        <span class="text-sm font-medium text-gray-900 ml-1">{{ $customerData['full_name'] }}</span>
                    </div>
                @endif
                
                @if(isset($customerData['email']))
                    <div>
                        <span class="text-xs text-gray-600">E-Mail:</span>
                        <span class="text-sm font-medium text-gray-900 ml-1">{{ $customerData['email'] }}</span>
                    </div>
                @endif
                
                @if(isset($customerData['company_name']))
                    <div>
                        <span class="text-xs text-gray-600">Firma:</span>
                        <span class="text-sm font-medium text-gray-900 ml-1">{{ $customerData['company_name'] }}</span>
                    </div>
                @endif
                
                @if(isset($customerData['date_of_birth']))
                    <div>
                        <span class="text-xs text-gray-600">Geburtsdatum:</span>
                        <span class="text-sm font-medium text-gray-900 ml-1">{{ $customerData['date_of_birth'] }}</span>
                    </div>
                @endif
            </div>
            
            {{-- Versicherungsdaten --}}
            <div class="space-y-2">
                <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Versicherung & Status</h5>
                
                @if(isset($customerData['insurance_type']))
                    <div>
                        <span class="text-xs text-gray-600">Versicherungsart:</span>
                        <x-customer-data-badge :customerData="$customerData" field="insurance_type" type="insurance" />
                    </div>
                @endif
                
                @if(isset($customerData['insurance_company']))
                    <div>
                        <span class="text-xs text-gray-600">Versicherung:</span>
                        <span class="text-sm font-medium text-gray-900 ml-1">{{ $customerData['insurance_company'] }}</span>
                    </div>
                @endif
                
                @if(isset($customerData['urgency']))
                    <div>
                        <span class="text-xs text-gray-600">Dringlichkeit:</span>
                        <x-customer-data-badge :customerData="$customerData" field="urgency" type="urgency" />
                    </div>
                @endif
                
                @if(isset($customerData['appointment_requested']) && $customerData['appointment_requested'])
                    <div>
                        <x-customer-data-badge :customerData="$customerData" field="appointment_requested" label="Terminwunsch" type="appointment" />
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Anrufgrund --}}
        @if(isset($customerData['reason_for_visit']))
            <div class="mt-4 pt-4 border-t border-blue-200">
                <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Anrufgrund</h5>
                <p class="text-sm text-gray-900">{{ $customerData['reason_for_visit'] }}</p>
            </div>
        @endif
        
        {{-- Kommentare --}}
        @if(isset($customerData['comments']))
            <div class="mt-4 pt-4 border-t border-blue-200">
                <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Kommentare</h5>
                <p class="text-sm text-gray-900">{{ $customerData['comments'] }}</p>
            </div>
        @endif
    </div>
@endif