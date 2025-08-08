@php
    use App\Models\Company;
    use App\Models\Call;
    use App\Models\Appointment;
    use App\Models\Service;
    use Carbon\Carbon;
    
    // Get selected company from Livewire component
    $selectedCompanyId = $this->companyId ?? null;
    $selectedPeriod = $this->period ?? 'week';
    
    // Get companies for dropdown
    $companies = Company::all();
    
    // Calculate date range based on period
    $endDate = now();
    $startDate = match($selectedPeriod) {
        'today' => now()->startOfDay(),
        'week' => now()->subWeek(),
        'month' => now()->subMonth(),
        'year' => now()->subYear(),
        default => now()->subWeek()
    };
    
    // Build query with optional company filter
    $appointmentsQuery = Appointment::whereBetween('starts_at', [$startDate, $endDate]);
    $callsQuery = Call::whereBetween('created_at', [$startDate, $endDate]);
    
    if ($selectedCompanyId) {
        $appointmentsQuery->where('company_id', $selectedCompanyId);
        $callsQuery->where('company_id', $selectedCompanyId);
    }
    
    // Calculate metrics
    $totalAppointments = $appointmentsQuery->count();
    $completedAppointments = (clone $appointmentsQuery)->where('status', 'completed')->count();
    $totalCalls = $callsQuery->count();
    $answeredCalls = (clone $callsQuery)->where('call_status', 'ended')->count();
    
    // Calculate revenue
    $totalRevenue = Appointment::query()
        ->join('services', 'appointments.service_id', '=', 'services.id')
        ->whereBetween('appointments.starts_at', [$startDate, $endDate])
        ->where('appointments.status', 'completed')
        ->when($selectedCompanyId, fn($q) => $q->where('appointments.company_id', $selectedCompanyId))
        ->sum('services.price');
    
    $conversionRate = $totalCalls > 0 ? round(($totalAppointments / $totalCalls) * 100, 1) : 0;
    
    // Get calls/appointments for today specifically
    $callsToday = Call::whereDate('created_at', today())
        ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
        ->count();
    
    $appointmentsToday = Appointment::whereDate('starts_at', today())
        ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
        ->count();
@endphp

<div style="padding: 20px; background: #f9fafb; min-height: 100vh;">
    
    <!-- Header with current selection -->
    <div style="background: linear-gradient(to right, #10b981, #059669); color: white; padding: 15px; margin: -20px -20px 20px -20px; font-weight: bold; font-size: 16px;">
        🎯 ANALYTICS DASHBOARD - {{ $selectedCompanyId ? Company::find($selectedCompanyId)->name : 'ALLE FIRMEN' }} - {{ now()->format('d.m.Y H:i:s') }}
    </div>
    
    <!-- Filter Section with wire:model -->
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 15px 0; color: #111827; font-size: 18px;">Filter</h2>
        <div style="display: flex; gap: 20px;">
            <div>
                <label style="display: block; margin-bottom: 5px; color: #6b7280; font-size: 14px;">Firma</label>
                <select wire:model.live="companyId" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; min-width: 200px;">
                    <option value="">Alle Firmen</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; color: #6b7280; font-size: 14px;">Zeitraum</label>
                <select wire:model.live="period" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; min-width: 150px;">
                    <option value="today">Heute</option>
                    <option value="week">Diese Woche</option>
                    <option value="month">Dieser Monat</option>
                    <option value="year">Dieses Jahr</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- KPI Grid with dynamic data -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
        
        <!-- Umsatz -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #f0fdf4; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <span style="color: #10b981; font-size: 20px;">€</span>
                </div>
                <span style="color: #6b7280; font-size: 14px;">Gesamt-Umsatz</span>
            </div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;">
                {{ number_format($totalRevenue, 2, ',', '.') }} €
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                <span style="color: #6b7280;">{{ ucfirst($selectedPeriod == 'today' ? 'Heute' : ($selectedPeriod == 'week' ? 'Diese Woche' : ($selectedPeriod == 'month' ? 'Dieser Monat' : 'Dieses Jahr'))) }}</span>
            </div>
        </div>
        
        <!-- Anrufe -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <span style="color: #3b82f6; font-size: 20px;">📞</span>
                </div>
                <span style="color: #6b7280; font-size: 14px;">Anrufe</span>
            </div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;">
                {{ $totalCalls }}
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                <span style="color: #10b981;">{{ $callsToday }} heute</span>
                <span style="color: #9ca3af; margin-left: 8px;">({{ $answeredCalls }} beantwortet)</span>
            </div>
        </div>
        
        <!-- Termine -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #fef3c7; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <span style="color: #f59e0b; font-size: 20px;">📅</span>
                </div>
                <span style="color: #6b7280; font-size: 14px;">Termine</span>
            </div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;">
                {{ $totalAppointments }}
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                <span style="color: #10b981;">{{ $appointmentsToday }} heute</span>
                <span style="color: #9ca3af; margin-left: 8px;">({{ $completedAppointments }} abgeschlossen)</span>
            </div>
        </div>
        
        <!-- Conversion -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <span style="color: #6b7280; font-size: 20px;">📊</span>
                </div>
                <span style="color: #6b7280; font-size: 14px;">Conversion Rate</span>
            </div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;">
                {{ number_format($conversionRate, 1, ',', '.') }}%
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                <span style="color: #9ca3af;">Anrufe zu Terminen</span>
            </div>
        </div>
    </div>
    
    <!-- Daily Stats Chart -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3 style="margin: 0 0 20px 0; color: #111827; font-size: 16px;">Tägliche Statistik (letzte 7 Tage)</h3>
        <div style="display: flex; align-items: flex-end; height: 200px; gap: 10px;">
            @php
                $dailyStats = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $dayAppointments = Appointment::whereDate('starts_at', $date)
                        ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                        ->count();
                    $dayCalls = Call::whereDate('created_at', $date)
                        ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                        ->count();
                    $dailyStats[] = [
                        'day' => $date->format('D'),
                        'date' => $date->format('d.m'),
                        'appointments' => $dayAppointments,
                        'calls' => $dayCalls
                    ];
                }
                $maxValue = max(array_merge(array_column($dailyStats, 'appointments'), array_column($dailyStats, 'calls'))) ?: 1;
            @endphp
            
            @foreach($dailyStats as $stat)
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                    <div style="display: flex; gap: 2px; align-items: flex-end; height: 150px;">
                        <div style="background: #3b82f6; width: 20px; height: {{ ($stat['calls'] / $maxValue) * 150 }}px;" title="Anrufe: {{ $stat['calls'] }}"></div>
                        <div style="background: #10b981; width: 20px; height: {{ ($stat['appointments'] / $maxValue) * 150 }}px;" title="Termine: {{ $stat['appointments'] }}"></div>
                    </div>
                    <div style="margin-top: 8px; font-size: 11px; color: #6b7280;">{{ $stat['day'] }}</div>
                    <div style="font-size: 10px; color: #9ca3af;">{{ $stat['date'] }}</div>
                </div>
            @endforeach
        </div>
        <div style="display: flex; gap: 20px; margin-top: 20px; justify-content: center;">
            <div style="display: flex; align-items: center; gap: 5px;">
                <div style="width: 12px; height: 12px; background: #3b82f6;"></div>
                <span style="font-size: 12px; color: #6b7280;">Anrufe</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
                <div style="width: 12px; height: 12px; background: #10b981;"></div>
                <span style="font-size: 12px; color: #6b7280;">Termine</span>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 20px 0; color: #111827; font-size: 16px;">Letzte Aktivitäten</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Zeit</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Firma</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Kunde</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Typ</th>
                    <th style="text-align: right; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $recentActivities = [];
                    
                    // Get recent appointments
                    $recentAppointments = Appointment::with(['company', 'customer'])
                        ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                        ->orderBy('created_at', 'desc')
                        ->limit(3)
                        ->get();
                    
                    foreach ($recentAppointments as $apt) {
                        $recentActivities[] = [
                            'time' => $apt->created_at->format('H:i'),
                            'company' => $apt->company->name ?? 'Unbekannt',
                            'customer' => $apt->customer->name ?? 'Gast',
                            'type' => 'Termin',
                            'status' => $apt->status == 'completed' ? 'Abgeschlossen' : 'Geplant'
                        ];
                    }
                    
                    // Get recent calls
                    $recentCalls = Call::with(['company'])
                        ->when($selectedCompanyId, fn($q) => $q->where('company_id', $selectedCompanyId))
                        ->orderBy('created_at', 'desc')
                        ->limit(2)
                        ->get();
                    
                    foreach ($recentCalls as $call) {
                        $recentActivities[] = [
                            'time' => $call->created_at->format('H:i'),
                            'company' => $call->company->name ?? 'Unbekannt',
                            'customer' => $call->from_number ?? 'Anonym',
                            'type' => 'Anruf',
                            'status' => $call->call_status == 'ended' ? 'Beendet' : 'Verpasst'
                        ];
                    }
                    
                    // Sort by time
                    usort($recentActivities, fn($a, $b) => strcmp($b['time'], $a['time']));
                @endphp
                
                @forelse($recentActivities as $activity)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $activity['time'] }}</td>
                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $activity['company'] }}</td>
                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $activity['customer'] }}</td>
                    <td style="padding: 12px;">
                        <span style="background: {{ $activity['type'] == 'Termin' ? '#fef3c7' : '#eff6ff' }}; color: {{ $activity['type'] == 'Termin' ? '#f59e0b' : '#3b82f6' }}; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            {{ $activity['type'] }}
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: right;">
                        <span style="background: {{ in_array($activity['status'], ['Abgeschlossen', 'Beendet']) ? '#f0fdf4' : '#f3f4f6' }}; color: {{ in_array($activity['status'], ['Abgeschlossen', 'Beendet']) ? '#10b981' : '#6b7280' }}; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            {{ $activity['status'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center; color: #9ca3af;">
                        Keine Aktivitäten gefunden
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
</div>