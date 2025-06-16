<div class="p-6 space-y-6">
    @php
        use App\Models\Staff;
        use Carbon\Carbon;
        
        // Get all active staff with their appointments for the current week
        $staffMembers = Staff::where('is_active', true)
            ->with(['appointments' => function ($query) {
                $query->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->whereNotIn('status', ['cancelled']);
            }, 'workingHours', 'company', 'homeBranch'])
            ->get();
            
        // Calculate workload for each staff member
        $workloadData = $staffMembers->map(function ($staff) {
            $totalWorkingMinutes = 0;
            $bookedMinutes = 0;
            
            // Calculate total working hours for the week
            foreach ($staff->workingHours as $wh) {
                if ($wh->is_active) {
                    $start = Carbon::parse($wh->start_time);
                    $end = Carbon::parse($wh->end_time);
                    $break = 0;
                    
                    if ($wh->break_start_time && $wh->break_end_time) {
                        $breakStart = Carbon::parse($wh->break_start_time);
                        $breakEnd = Carbon::parse($wh->break_end_time);
                        $break = $breakStart->diffInMinutes($breakEnd);
                    }
                    
                    $totalWorkingMinutes += $start->diffInMinutes($end) - $break;
                }
            }
            
            // Calculate booked time
            $bookedMinutes = $staff->appointments->sum(function ($apt) {
                return $apt->starts_at->diffInMinutes($apt->ends_at);
            });
            
            $utilizationRate = $totalWorkingMinutes > 0 ? round(($bookedMinutes / $totalWorkingMinutes) * 100, 1) : 0;
            
            return [
                'staff' => $staff,
                'total_hours' => round($totalWorkingMinutes / 60, 1),
                'booked_hours' => round($bookedMinutes / 60, 1),
                'utilization_rate' => $utilizationRate,
                'appointments_count' => $staff->appointments->count(),
            ];
        })->sortByDesc('utilization_rate');
        
        // Calculate team averages
        $avgUtilization = $workloadData->avg('utilization_rate');
        $totalAppointments = $workloadData->sum('appointments_count');
        $totalBookedHours = $workloadData->sum('booked_hours');
    @endphp

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Team-Auslastung</p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ round($avgUtilization, 1) }}%</p>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Durchschnitt diese Woche</p>
        </div>
        
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <p class="text-sm font-medium text-green-600 dark:text-green-400">Termine gesamt</p>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $totalAppointments }}</p>
            <p class="text-xs text-green-600 dark:text-green-400 mt-1">Diese Woche</p>
        </div>
        
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Gebuchte Stunden</p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">{{ round($totalBookedHours, 1) }}h</p>
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Von allen Mitarbeitern</p>
        </div>
    </div>

    {{-- Staff Workload List --}}
    <div class="space-y-4">
        <h4 class="font-semibold text-gray-900 dark:text-white">Mitarbeiter-Auslastung</h4>
        
        @foreach($workloadData as $data)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $data['staff']->name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $data['staff']->company?->name }} • {{ $data['staff']->homeBranch?->name }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold 
                            @if($data['utilization_rate'] >= 80) text-red-600 dark:text-red-400
                            @elseif($data['utilization_rate'] >= 60) text-amber-600 dark:text-amber-400
                            @else text-green-600 dark:text-green-400
                            @endif">
                            {{ $data['utilization_rate'] }}%
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Auslastung</p>
                    </div>
                </div>
                
                {{-- Progress Bar --}}
                <div class="mb-3">
                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300
                            @if($data['utilization_rate'] >= 80) bg-red-500
                            @elseif($data['utilization_rate'] >= 60) bg-amber-500
                            @else bg-green-500
                            @endif" 
                            style="width: {{ min($data['utilization_rate'], 100) }}%">
                        </div>
                    </div>
                </div>
                
                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Termine</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $data['appointments_count'] }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Gebucht</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $data['booked_hours'] }}h</p>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Verfügbar</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $data['total_hours'] }}h</p>
                    </div>
                </div>
                
                {{-- Warning for high workload --}}
                @if($data['utilization_rate'] >= 80)
                    <div class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 rounded text-sm text-red-700 dark:text-red-300">
                        <x-heroicon-m-exclamation-triangle class="inline w-4 h-4 mr-1" />
                        Hohe Auslastung - Überlastung vermeiden!
                    </div>
                @elseif($data['utilization_rate'] < 40)
                    <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-sm text-blue-700 dark:text-blue-300">
                        <x-heroicon-m-information-circle class="inline w-4 h-4 mr-1" />
                        Kapazität für weitere Termine vorhanden
                    </div>
                @endif
            </div>
        @endforeach
        
        @if($workloadData->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                Keine aktiven Mitarbeiter gefunden.
            </div>
        @endif
    </div>

    {{-- Recommendations --}}
    @if($avgUtilization > 75)
        <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
            <h4 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">Empfehlungen</h4>
            <ul class="space-y-1 text-sm text-amber-700 dark:text-amber-300">
                <li>• Die durchschnittliche Teamauslastung ist hoch. Erwägen Sie die Einstellung zusätzlicher Mitarbeiter.</li>
                <li>• Verteilen Sie Termine gleichmäßiger über die Woche.</li>
                <li>• Prüfen Sie, ob einige Services delegiert oder optimiert werden können.</li>
            </ul>
        </div>
    @elseif($avgUtilization < 40)
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Optimierungspotenzial</h4>
            <ul class="space-y-1 text-sm text-blue-700 dark:text-blue-300">
                <li>• Das Team hat noch Kapazitäten für weitere Termine.</li>
                <li>• Erwägen Sie Marketing-Maßnahmen zur Kundengewinnung.</li>
                <li>• Überprüfen Sie die Arbeitszeiten auf Optimierungsmöglichkeiten.</li>
            </ul>
        </div>
    @endif
</div>