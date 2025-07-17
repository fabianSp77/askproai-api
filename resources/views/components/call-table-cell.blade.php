@props(['call', 'column', 'key' => '', 'canViewCosts' => false])

@switch($key)
    @case('time_since')
        @php
            $timeSince = \App\Helpers\TimeHelper::formatTimeSince($call->created_at);
            $ageClass = \App\Helpers\TimeHelper::getAgeClass($call->created_at);
        @endphp
        <td class="px-4 py-4 whitespace-nowrap text-sm">
            <span class="{{ $ageClass }}" title="{{ $call->created_at->format('d.m.Y H:i:s') }}">
                {!! $timeSince !!}
            </span>
        </td>
        @break
        
    @case('caller_info')
        <td class="px-4 py-4 whitespace-nowrap">
            <div>
                <div class="text-sm font-medium text-gray-900">
                    @if($call->extracted_name || $call->customer)
                        {{ $call->extracted_name ?? $call->customer->name }}
                    @else
                        Unbekannt
                    @endif
                </div>
                <div class="text-sm text-gray-500">
                    {{ $call->phone_number ?? $call->from_number }}
                </div>
            </div>
        </td>
        @break
        
    @case('reason')
        <td class="px-4 py-4">
            <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $call->reason_for_visit }}">
                {{ $call->reason_for_visit ?: '-' }}
            </div>
        </td>
        @break
        
    @case('urgency')
        <td class="px-4 py-4 whitespace-nowrap">
            @if($call->urgency_level || isset($call->metadata['customer_data']['urgency']))
                <x-customer-data-badge 
                    :customerData="['urgency' => $call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? null]" 
                    field="urgency" 
                    type="urgency" />
            @else
                <span class="text-gray-500">-</span>
            @endif
        </td>
        @break
        
    @case('status')
        <td class="px-4 py-4 whitespace-nowrap">
            @php
                $status = $call->callPortalData->status ?? 'new';
                $statusLabels = [
                    'new' => 'Neu',
                    'in_progress' => 'In Bearbeitung',
                    'requires_action' => 'Aktion erforderlich',
                    'completed' => 'Abgeschlossen',
                    'callback_scheduled' => 'Rückruf geplant'
                ];
                $statusColors = [
                    'new' => 'bg-blue-100 text-blue-800',
                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                    'requires_action' => 'bg-red-100 text-red-800',
                    'completed' => 'bg-green-100 text-green-800',
                    'callback_scheduled' => 'bg-purple-100 text-purple-800'
                ];
            @endphp
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ $statusLabels[$status] ?? $status }}
            </span>
        </td>
        @break
        
    @case('assigned_to')
        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $call->callPortalData->assignedTo->name ?? 'Nicht zugewiesen' }}
        </td>
        @break
        
    @case('duration')
        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
            {{ gmdate('i:s', $call->duration_sec ?? 0) }}
        </td>
        @break
        
    @case('costs')
        @if($canViewCosts)
            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                @php
                    $cost = 0;
                    if ($call->charge) {
                        $cost = $call->charge->amount_charged;
                    } elseif ($call->duration_sec) {
                        $pricing = \App\Models\CompanyPricing::getCurrentForCompany($call->company_id);
                        if ($pricing) {
                            $cost = $pricing->calculatePrice($call->duration_sec);
                        } else {
                            $billingRate = \App\Models\BillingRate::where('company_id', $call->company_id)->active()->first();
                            if ($billingRate) {
                                $cost = $billingRate->calculateCharge($call->duration_sec);
                            }
                        }
                    }
                @endphp
                {{ number_format($cost, 2, ',', '.') }} €
            </td>
        @endif
        @break
        
    @case('phone_number')
        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $call->phone_number ?? $call->from_number }}
        </td>
        @break
        
    @case('created_at')
        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ $call->created_at->format('d.m.Y H:i') }}
        </td>
        @break
@endswitch