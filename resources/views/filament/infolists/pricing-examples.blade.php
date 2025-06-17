@php
    $examples = [
        ['minutes' => 50, 'label' => 'Wenig-Nutzer'],
        ['minutes' => $includedMinutes, 'label' => 'Genau Inklusive'],
        ['minutes' => 200, 'label' => 'Normal-Nutzer'],
        ['minutes' => 500, 'label' => 'Viel-Nutzer'],
    ];
    
    function calculatePrice($minutes, $includedMinutes, $pricePerMinute, $overagePrice, $monthlyFee) {
        if ($minutes <= $includedMinutes) {
            return $monthlyFee;
        }
        
        $overageMinutes = $minutes - $includedMinutes;
        $minuteCost = $overageMinutes * ($overagePrice ?: $pricePerMinute);
        
        return $minuteCost + $monthlyFee;
    }
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($examples as $example)
            @php
                $totalCost = calculatePrice($example['minutes'], $includedMinutes, $pricePerMinute, $overagePrice, $monthlyFee);
                $costPerMinute = $example['minutes'] > 0 ? $totalCost / $example['minutes'] : 0;
                $isOverIncluded = $example['minutes'] > $includedMinutes;
            @endphp
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $example['label'] }}
                </h5>
                
                <div class="text-2xl font-bold {{ $isOverIncluded ? 'text-orange-600' : 'text-green-600' }} mb-1">
                    {{ $example['minutes'] }} Min
                </div>
                
                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    €{{ number_format($totalCost, 2, ',', '.') }}
                </div>
                
                <div class="text-xs text-gray-500 mt-1">
                    €{{ number_format($costPerMinute, 4, ',', '.') }}/Min
                </div>
                
                @if($isOverIncluded)
                    <div class="text-xs text-orange-600 mt-2">
                        +{{ $example['minutes'] - $includedMinutes }} Zusatzminuten
                    </div>
                @else
                    <div class="text-xs text-green-600 mt-2">
                        Innerhalb Inklusive
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mt-4">
        <h5 class="text-sm font-medium text-blue-800 dark:text-blue-300 mb-2">
            Abrechnungslogik:
        </h5>
        <ul class="text-xs text-blue-700 dark:text-blue-400 space-y-1">
            <li>• Grundgebühr: €{{ number_format($monthlyFee, 2, ',', '.') }}/Monat</li>
            <li>• Inklusive: {{ $includedMinutes }} Minuten</li>
            <li>• Standard-Minutenpreis: €{{ number_format($pricePerMinute, 4, ',', '.') }}</li>
            @if($overagePrice && $overagePrice != $pricePerMinute)
                <li>• Preis für Zusatzminuten: €{{ number_format($overagePrice, 4, ',', '.') }}</li>
            @endif
            @if($setupFee > 0)
                <li>• Einmalige Einrichtung: €{{ number_format($setupFee, 2, ',', '.') }}</li>
            @endif
        </ul>
    </div>
</div>