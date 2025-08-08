@php
    use App\Models\Company;
    $companies = Company::all();
    $totalRevenue = 2847.50;
    $callsToday = 47;
    $newAppointments = 23;
    $conversionRate = 68.5;
@endphp

<div style="padding: 20px; background: #f9fafb; min-height: 100vh;">
    
    <!-- WICHTIG: Debug Banner zeigt Version -->
    <div style="background: linear-gradient(to right, #10b981, #059669); color: white; padding: 15px; margin: -20px -20px 20px -20px; font-weight: bold; font-size: 16px;">
        🎯 ANALYTICS DASHBOARD - DEUTSCHE VERSION - {{ now()->format('d.m.Y H:i:s') }}
    </div>
    
    <!-- Filter Section -->
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 15px 0; color: #111827; font-size: 18px;">Filter</h2>
        <div style="display: flex; gap: 20px;">
            <select style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                <option>Alle Unternehmen</option>
                @foreach($companies as $company)
                    <option>{{ $company->name }}</option>
                @endforeach
            </select>
            <select style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                <option>Diese Woche</option>
                <option>Dieser Monat</option>
                <option>Dieses Jahr</option>
            </select>
        </div>
    </div>
    
    <!-- KPI Grid -->
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
                <span style="color: #10b981;">↑ 12,3%</span>
                <span style="color: #9ca3af; margin-left: 8px;">zum Vormonat</span>
            </div>
        </div>
        
        <!-- Anrufe -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #eff6ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <span style="color: #3b82f6; font-size: 20px;">📞</span>
                </div>
                <span style="color: #6b7280; font-size: 14px;">Anrufe Heute</span>
            </div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;">
                {{ $callsToday }}
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                <span style="color: #10b981;">↑ 8</span>
                <span style="color: #9ca3af; margin-left: 8px;">seit gestern</span>
            </div>
        </div>
        
        <!-- Termine -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #fef3c7; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                    <span style="color: #f59e0b; font-size: 20px;">📅</span>
                </div>
                <span style="color: #6b7280; font-size: 14px;">Neue Termine</span>
            </div>
            <div style="font-size: 32px; font-weight: bold; color: #111827;">
                {{ $newAppointments }}
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                <span style="color: #10b981;">↑ 15%</span>
                <span style="color: #9ca3af; margin-left: 8px;">diese Woche</span>
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
                <span style="color: #10b981;">↑ 2,1%</span>
                <span style="color: #9ca3af; margin-left: 8px;">Verbesserung</span>
            </div>
        </div>
    </div>
    
    <!-- Simple Bar Charts ohne JavaScript -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
        
        <!-- Umsatz Balken -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0; color: #111827; font-size: 16px;">Umsatzentwicklung (Woche)</h3>
            <div style="display: flex; align-items: flex-end; height: 200px; gap: 10px;">
                @php
                    $days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
                    $values = [1200, 1900, 3000, 2500, 2700, 3200, 2900];
                    $max = max($values);
                @endphp
                @foreach($days as $index => $day)
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                        <div style="background: #3b82f6; width: 100%; height: {{ ($values[$index] / $max) * 150 }}px; border-radius: 4px 4px 0 0;"></div>
                        <div style="margin-top: 8px; font-size: 12px; color: #6b7280;">{{ $day }}</div>
                        <div style="font-size: 10px; color: #9ca3af;">{{ number_format($values[$index], 0, ',', '.') }}€</div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Anrufe Balken -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 20px 0; color: #111827; font-size: 16px;">Anrufstatistik (Heute)</h3>
            <div style="display: flex; align-items: flex-end; height: 200px; gap: 10px;">
                @php
                    $hours = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];
                    $calls = [12, 19, 23, 17, 25, 15];
                    $maxCalls = max($calls);
                @endphp
                @foreach($hours as $index => $hour)
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                        <div style="background: #10b981; width: 100%; height: {{ ($calls[$index] / $maxCalls) * 150 }}px; border-radius: 4px 4px 0 0;"></div>
                        <div style="margin-top: 8px; font-size: 11px; color: #6b7280;">{{ $hour }}</div>
                        <div style="font-size: 10px; color: #9ca3af;">{{ $calls[$index] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Aktivitäten Tabelle -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
        <h3 style="margin: 0 0 20px 0; color: #111827; font-size: 16px;">Letzte Aktivitäten</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Zeit</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Unternehmen</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Kunde</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Aktion</th>
                    <th style="text-align: right; padding: 12px; color: #6b7280; font-size: 14px; font-weight: 500;">Betrag</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $activities = [
                        ['time' => '09:15', 'company' => 'Beispiel GmbH', 'customer' => 'Max Mustermann', 'action' => 'Termin gebucht', 'amount' => 120.00],
                        ['time' => '10:30', 'company' => 'Test AG', 'customer' => 'Anna Schmidt', 'action' => 'Anruf beendet', 'amount' => 0],
                        ['time' => '11:45', 'company' => 'Demo KG', 'customer' => 'Peter Weber', 'action' => 'Termin gebucht', 'amount' => 85.00],
                    ];
                @endphp
                @foreach($activities as $activity)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $activity['time'] }}</td>
                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $activity['company'] }}</td>
                    <td style="padding: 12px; color: #111827; font-size: 14px;">{{ $activity['customer'] }}</td>
                    <td style="padding: 12px;">
                        <span style="background: #f0fdf4; color: #10b981; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            {{ $activity['action'] }}
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: right; color: #111827; font-size: 14px; font-weight: 500;">
                        {{ number_format($activity['amount'], 2, ',', '.') }} €
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
</div>