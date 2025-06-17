<div x-data="{
    pricePerMinute: {{ $pricePerMinute ?? 0 }},
    includedMinutes: {{ $includedMinutes ?? 0 }},
    overagePrice: {{ $overagePrice ?? 0 }},
    monthlyFee: {{ $monthlyFee ?? 0 }},
    testMinutes: 100,
    
    calculate() {
        let billableMinutes = 0;
        let includedUsed = 0;
        let overageMinutes = 0;
        let minuteCost = 0;
        
        if (this.testMinutes <= this.includedMinutes) {
            includedUsed = this.testMinutes;
            billableMinutes = 0;
        } else {
            includedUsed = this.includedMinutes;
            overageMinutes = this.testMinutes - this.includedMinutes;
            billableMinutes = overageMinutes;
            minuteCost = overageMinutes * (this.overagePrice || this.pricePerMinute);
        }
        
        return {
            total: minuteCost + this.monthlyFee,
            minuteCost: minuteCost,
            includedUsed: includedUsed,
            overageMinutes: overageMinutes,
            costPerMinute: this.testMinutes > 0 ? (minuteCost + this.monthlyFee) / this.testMinutes : 0
        };
    }
}" class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Beispielrechnung</h4>
        
        <div class="space-y-3">
            <div>
                <label class="text-xs text-gray-600 dark:text-gray-400">Test-Minuten:</label>
                <input type="number" x-model="testMinutes" min="0" step="10" 
                    class="w-full mt-1 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md">
            </div>
            
            <div x-show="testMinutes > 0" class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Inklusivminuten genutzt:</span>
                    <span x-text="calculate().includedUsed + ' Min'"></span>
                </div>
                
                <div class="flex justify-between" x-show="calculate().overageMinutes > 0">
                    <span class="text-gray-600 dark:text-gray-400">Zusätzliche Minuten:</span>
                    <span x-text="calculate().overageMinutes + ' Min'"></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Minutenkosten:</span>
                    <span x-text="'€' + calculate().minuteCost.toFixed(2).replace('.', ',')"></span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Grundgebühr:</span>
                    <span x-text="'€' + monthlyFee.toFixed(2).replace('.', ',')"></span>
                </div>
                
                <div class="pt-2 border-t flex justify-between font-bold">
                    <span>Gesamtkosten:</span>
                    <span x-text="'€' + calculate().total.toFixed(2).replace('.', ',')"></span>
                </div>
                
                <div class="text-xs text-gray-500">
                    <span>Effektiv pro Minute: </span>
                    <span x-text="'€' + calculate().costPerMinute.toFixed(4).replace('.', ',')"></span>
                </div>
            </div>
        </div>
    </div>
</div>