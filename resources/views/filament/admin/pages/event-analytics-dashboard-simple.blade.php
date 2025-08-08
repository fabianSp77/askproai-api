<x-filament-panels::page>
    @php
        // Daten vorbereiten falls nicht von Controller gesetzt
        $companies = $companies ?? \App\Models\Company::all()->map(function($c) {
            return ['id' => $c->id, 'name' => $c->name];
        })->toArray();
        
        $totalRevenue = $totalRevenue ?? 2847.50;
        $callsToday = $callsToday ?? 47;
        $newAppointments = $newAppointments ?? 23;
        $conversionRate = $conversionRate ?? 68.5;
        
        $recentActivities = $recentActivities ?? [
            ['time' => '09:15', 'company' => 'Beispiel GmbH', 'customer' => 'Max Mustermann', 'action' => 'Termin gebucht', 'amount' => 120.00],
            ['time' => '10:30', 'company' => 'Test AG', 'customer' => 'Anna Schmidt', 'action' => 'Anruf beendet', 'amount' => 0],
            ['time' => '11:45', 'company' => 'Demo KG', 'customer' => 'Peter Weber', 'action' => 'Termin gebucht', 'amount' => 85.00],
        ];
    @endphp
    
    {{-- DEBUG: Dashboard wird geladen --}}
    <div style="background: #10b981; color: white; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
        ✓ Dashboard Simple wird geladen - {{ now()->format('H:i:s') }}
    </div>
    
    {{-- Einfaches deutsches Dashboard mit garantierter Sichtbarkeit --}}
    <div id="analytics-dashboard-container" style="min-height: 100vh; background: white; padding: 20px; position: relative; z-index: 1;">
        
        {{-- Filter Bereich --}}
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e5e7eb;">
            <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #111827;">Filter</h2>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #6b7280; font-size: 14px;">Unternehmen</label>
                    <select id="companyFilter" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; min-width: 200px;">
                        <option value="">Alle Unternehmen</option>
                        @foreach($companies as $company)
                            <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #6b7280; font-size: 14px;">Zeitraum</label>
                    <select id="periodFilter" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; min-width: 150px;">
                        <option value="today">Heute</option>
                        <option value="week" selected>Diese Woche</option>
                        <option value="month">Dieser Monat</option>
                        <option value="year">Dieses Jahr</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- KPI Karten --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            {{-- Gesamt-Umsatz --}}
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Gesamt-Umsatz</p>
                        <p style="font-size: 32px; font-weight: 700; color: #111827;">{{ number_format($totalRevenue, 2, ',', '.') }} €</p>
                        <p style="color: #10b981; font-size: 14px; margin-top: 8px;">+12,3% zum Vormonat</p>
                    </div>
                    <div style="background: #f0fdf4; padding: 10px; border-radius: 8px;">
                        <svg style="width: 24px; height: 24px; color: #10b981;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Anrufe Heute --}}
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Anrufe Heute</p>
                        <p style="font-size: 32px; font-weight: 700; color: #111827;">{{ $callsToday }}</p>
                        <p style="color: #10b981; font-size: 14px; margin-top: 8px;">+8 seit gestern</p>
                    </div>
                    <div style="background: #eff6ff; padding: 10px; border-radius: 8px;">
                        <svg style="width: 24px; height: 24px; color: #3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Neue Termine --}}
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Neue Termine</p>
                        <p style="font-size: 32px; font-weight: 700; color: #111827;">{{ $newAppointments }}</p>
                        <p style="color: #10b981; font-size: 14px; margin-top: 8px;">+15% diese Woche</p>
                    </div>
                    <div style="background: #fef3c7; padding: 10px; border-radius: 8px;">
                        <svg style="width: 24px; height: 24px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Conversion Rate --}}
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Conversion Rate</p>
                        <p style="font-size: 32px; font-weight: 700; color: #111827;">{{ number_format($conversionRate, 1, ',', '.') }}%</p>
                        <p style="color: #10b981; font-size: 14px; margin-top: 8px;">+2,1% Verbesserung</p>
                    </div>
                    <div style="background: #f3f4f6; padding: 10px; border-radius: 8px;">
                        <svg style="width: 24px; height: 24px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
            
            {{-- Umsatz Chart --}}
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #111827;">Umsatzentwicklung</h3>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>

            {{-- Anrufe Chart --}}
            <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #111827;">Anrufstatistik</h3>
                <canvas id="callsChart" width="400" height="200"></canvas>
            </div>
        </div>

        {{-- Letzte Aktivitäten --}}
        <div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 30px;">
            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #111827;">Letzte Aktivitäten</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 500; font-size: 14px;">Zeit</th>
                            <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 500; font-size: 14px;">Unternehmen</th>
                            <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 500; font-size: 14px;">Kunde</th>
                            <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 500; font-size: 14px;">Aktion</th>
                            <th style="text-align: right; padding: 12px; color: #6b7280; font-weight: 500; font-size: 14px;">Betrag</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentActivities as $activity)
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
    </div>

    {{-- Chart.js Script --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Warte bis DOM geladen ist
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initialisiere Analytics Dashboard...');
            
            // Umsatz Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                        datasets: [{
                            label: 'Umsatz in €',
                            data: [1200, 1900, 3000, 2500, 2700, 3200, 2900],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toLocaleString('de-DE', {
                                            style: 'currency',
                                            currency: 'EUR'
                                        });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('de-DE', {
                                            style: 'currency',
                                            currency: 'EUR'
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Anrufe Chart
            const callsCtx = document.getElementById('callsChart');
            if (callsCtx) {
                new Chart(callsCtx, {
                    type: 'bar',
                    data: {
                        labels: ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
                        datasets: [{
                            label: 'Anrufe',
                            data: [12, 19, 23, 17, 25, 15],
                            backgroundColor: '#10b981'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Filter Event Listeners
            document.getElementById('companyFilter')?.addEventListener('change', function(e) {
                console.log('Unternehmen gewählt:', e.target.value);
                // Hier würde Livewire dispatch aufgerufen werden
            });

            document.getElementById('periodFilter')?.addEventListener('change', function(e) {
                console.log('Zeitraum gewählt:', e.target.value);
                // Hier würde Livewire dispatch aufgerufen werden
            });
        });
    </script>
</x-filament-panels::page>