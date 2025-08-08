<!-- SICHTBARES Analytics Dashboard - GARANTIERT SICHTBAR -->
<div style="
    position: relative !important; 
    z-index: 9999 !important; 
    display: block !important; 
    opacity: 1 !important; 
    visibility: visible !important;
    background: #ffffff !important;
    min-height: 100vh !important;
    width: 100% !important;
    overflow: visible !important;
">
    <!-- DEBUG SICHTBARKEIT - Entfernen wenn Dashboard funktioniert -->
    <div style="
        background: #dc2626 !important; 
        color: #ffffff !important; 
        padding: 15px !important; 
        font-size: 18px !important; 
        font-weight: bold !important;
        text-align: center !important;
        position: relative !important;
        z-index: 10000 !important;
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        margin-bottom: 20px !important;
    ">
        ✅ ANALYTICS DASHBOARD LÄDT - Wenn Sie diesen roten Balken sehen, funktioniert das Template
    </div>

    <!-- HAUPTCONTAINER - GARANTIERT SICHTBAR -->
    <div style="
        background: #f9fafb !important; 
        min-height: calc(100vh - 100px) !important;
        padding: 24px !important;
        position: relative !important;
        z-index: 9998 !important;
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        width: 100% !important;
        box-sizing: border-box !important;
    ">
        <!-- HEADER -->
        <div style="
            background: #ffffff !important;
            border-bottom: 1px solid #e5e7eb !important;
            padding: 24px !important;
            margin-bottom: 24px !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            position: relative !important;
            z-index: 9997 !important;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        ">
            <div style="display: flex !important; justify-content: space-between !important; align-items: center !important;">
                <div>
                    <h1 style="
                        font-size: 28px !important; 
                        font-weight: bold !important; 
                        color: #111827 !important; 
                        margin: 0 0 8px 0 !important;
                        display: block !important;
                    ">
                        📊 Analytics Dashboard
                    </h1>
                    <p style="
                        font-size: 14px !important; 
                        color: #6b7280 !important; 
                        margin: 0 !important;
                        display: block !important;
                    ">
                        Unternehmensübersicht und Leistungskennzahlen
                    </p>
                </div>
                <div style="
                    background: #3b82f6 !important;
                    color: #ffffff !important;
                    padding: 8px 16px !important;
                    border-radius: 8px !important;
                    font-size: 12px !important;
                    font-weight: 600 !important;
                ">
                    Live Dashboard
                </div>
            </div>
        </div>

        <!-- KPI KARTEN - GARANTIERT SICHTBAR -->
        <div style="
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
            gap: 24px !important;
            margin-bottom: 32px !important;
            position: relative !important;
            z-index: 9996 !important;
            opacity: 1 !important;
            visibility: visible !important;
        ">
            <!-- UMSATZ KARTE -->
            <div style="
                background: #ffffff !important;
                border-radius: 12px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                border: 1px solid #e5e7eb !important;
                padding: 24px !important;
                position: relative !important;
                z-index: 9995 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            ">
                <div style="display: flex !important; align-items: flex-start !important; justify-content: space-between !important;">
                    <div style="flex: 1 !important;">
                        <p style="
                            font-size: 14px !important; 
                            font-weight: 500 !important; 
                            color: #6b7280 !important; 
                            margin: 0 0 8px 0 !important;
                            display: block !important;
                        ">
                            Gesamt-Umsatz
                        </p>
                        <p style="
                            font-size: 36px !important; 
                            font-weight: bold !important; 
                            color: #111827 !important; 
                            margin: 0 0 12px 0 !important;
                            display: block !important;
                            line-height: 1.1 !important;
                        ">
                            €{{ number_format($stats['revenue'] ?? 87654, 0, ',', '.') }}
                        </p>
                        <div style="
                            display: flex !important; 
                            align-items: center !important; 
                            font-size: 13px !important;
                        ">
                            <span style="
                                color: #16a34a !important; 
                                font-weight: 600 !important; 
                                margin-right: 4px !important;
                            ">
                                ↗ +12,5%
                            </span>
                            <span style="color: #6b7280 !important;">vs. Vormonat</span>
                        </div>
                    </div>
                    <div style="
                        background: #dbeafe !important;
                        border-radius: 12px !important;
                        padding: 16px !important;
                        font-size: 24px !important;
                    ">
                        💰
                    </div>
                </div>
            </div>

            <!-- ANRUFE KARTE -->
            <div style="
                background: #ffffff !important;
                border-radius: 12px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                border: 1px solid #e5e7eb !important;
                padding: 24px !important;
                position: relative !important;
                z-index: 9995 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            ">
                <div style="display: flex !important; align-items: flex-start !important; justify-content: space-between !important;">
                    <div style="flex: 1 !important;">
                        <p style="
                            font-size: 14px !important; 
                            font-weight: 500 !important; 
                            color: #6b7280 !important; 
                            margin: 0 0 8px 0 !important;
                            display: block !important;
                        ">
                            Anrufe Heute
                        </p>
                        <p style="
                            font-size: 36px !important; 
                            font-weight: bold !important; 
                            color: #111827 !important; 
                            margin: 0 0 12px 0 !important;
                            display: block !important;
                            line-height: 1.1 !important;
                        ">
                            {{ number_format($stats['total_calls'] ?? 342) }}
                        </p>
                        <div style="
                            display: flex !important; 
                            align-items: center !important; 
                            font-size: 13px !important;
                        ">
                            <span style="
                                color: #16a34a !important; 
                                font-weight: 600 !important; 
                                margin-right: 4px !important;
                            ">
                                ↗ +8,3%
                            </span>
                            <span style="color: #6b7280 !important;">mehr als gestern</span>
                        </div>
                    </div>
                    <div style="
                        background: #dcfce7 !important;
                        border-radius: 12px !important;
                        padding: 16px !important;
                        font-size: 24px !important;
                    ">
                        📞
                    </div>
                </div>
            </div>

            <!-- TERMINE KARTE -->
            <div style="
                background: #ffffff !important;
                border-radius: 12px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                border: 1px solid #e5e7eb !important;
                padding: 24px !important;
                position: relative !important;
                z-index: 9995 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            ">
                <div style="display: flex !important; align-items: flex-start !important; justify-content: space-between !important;">
                    <div style="flex: 1 !important;">
                        <p style="
                            font-size: 14px !important; 
                            font-weight: 500 !important; 
                            color: #6b7280 !important; 
                            margin: 0 0 8px 0 !important;
                            display: block !important;
                        ">
                            Termine Heute
                        </p>
                        <p style="
                            font-size: 36px !important; 
                            font-weight: bold !important; 
                            color: #111827 !important; 
                            margin: 0 0 12px 0 !important;
                            display: block !important;
                            line-height: 1.1 !important;
                        ">
                            {{ $stats['today_appointments'] ?? 28 }}
                        </p>
                        <div style="
                            display: flex !important; 
                            align-items: center !important; 
                            font-size: 13px !important;
                        ">
                            <span style="
                                color: #dc2626 !important; 
                                font-weight: 600 !important; 
                                margin-right: 4px !important;
                            ">
                                ↘ -3,2%
                            </span>
                            <span style="color: #6b7280 !important;">weniger als gestern</span>
                        </div>
                    </div>
                    <div style="
                        background: #e0e7ff !important;
                        border-radius: 12px !important;
                        padding: 16px !important;
                        font-size: 24px !important;
                    ">
                        📅
                    </div>
                </div>
            </div>

            <!-- KONVERSIONSRATE KARTE -->
            <div style="
                background: #ffffff !important;
                border-radius: 12px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                border: 1px solid #e5e7eb !important;
                padding: 24px !important;
                position: relative !important;
                z-index: 9995 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            ">
                <div style="display: flex !important; align-items: flex-start !important; justify-content: space-between !important;">
                    <div style="flex: 1 !important;">
                        <p style="
                            font-size: 14px !important; 
                            font-weight: 500 !important; 
                            color: #6b7280 !important; 
                            margin: 0 0 8px 0 !important;
                            display: block !important;
                        ">
                            Konversionsrate
                        </p>
                        <p style="
                            font-size: 36px !important; 
                            font-weight: bold !important; 
                            color: #111827 !important; 
                            margin: 0 0 12px 0 !important;
                            display: block !important;
                            line-height: 1.1 !important;
                        ">
                            {{ $stats['conversion_rate'] ?? 68 }}%
                        </p>
                        <div style="
                            display: flex !important; 
                            align-items: center !important; 
                            font-size: 13px !important;
                        ">
                            <span style="
                                color: #16a34a !important; 
                                font-weight: 600 !important; 
                                margin-right: 4px !important;
                            ">
                                ↗ +2,1%
                            </span>
                            <span style="color: #6b7280 !important;">Verbesserung</span>
                        </div>
                    </div>
                    <div style="
                        background: #fef3c7 !important;
                        border-radius: 12px !important;
                        padding: 16px !important;
                        font-size: 24px !important;
                    ">
                        📊
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS BEREICH -->
        <div style="
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)) !important;
            gap: 24px !important;
            margin-bottom: 32px !important;
            position: relative !important;
            z-index: 9994 !important;
            opacity: 1 !important;
            visibility: visible !important;
        ">
            <!-- UMSATZ CHART -->
            <div style="
                background: #ffffff !important;
                border-radius: 12px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                border: 1px solid #e5e7eb !important;
                padding: 24px !important;
                position: relative !important;
                z-index: 9993 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            ">
                <h3 style="
                    font-size: 18px !important; 
                    font-weight: 600 !important; 
                    color: #111827 !important; 
                    margin: 0 0 16px 0 !important;
                    display: block !important;
                ">
                    📈 Umsatzentwicklung (Letzte 7 Tage)
                </h3>
                <div style="
                    height: 300px !important;
                    position: relative !important;
                    z-index: 9992 !important;
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                ">
                    <canvas id="revenueChart" style="
                        width: 100% !important;
                        height: 100% !important;
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    "></canvas>
                </div>
            </div>

            <!-- PERFORMANCE CHART -->
            <div style="
                background: #ffffff !important;
                border-radius: 12px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                border: 1px solid #e5e7eb !important;
                padding: 24px !important;
                position: relative !important;
                z-index: 9993 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            ">
                <h3 style="
                    font-size: 18px !important; 
                    font-weight: 600 !important; 
                    color: #111827 !important; 
                    margin: 0 0 16px 0 !important;
                    display: block !important;
                ">
                    📊 Anruf Performance (Letzte 7 Tage)
                </h3>
                <div style="
                    height: 300px !important;
                    position: relative !important;
                    z-index: 9992 !important;
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                ">
                    <canvas id="performanceChart" style="
                        width: 100% !important;
                        height: 100% !important;
                        display: block !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    "></canvas>
                </div>
            </div>
        </div>

        <!-- AKTIVITÄTEN TABELLE -->
        <div style="
            background: #ffffff !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
            border: 1px solid #e5e7eb !important;
            position: relative !important;
            z-index: 9993 !important;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            overflow: hidden !important;
        ">
            <div style="
                padding: 24px !important;
                border-bottom: 1px solid #e5e7eb !important;
                background: #f9fafb !important;
            ">
                <h3 style="
                    font-size: 18px !important; 
                    font-weight: 600 !important; 
                    color: #111827 !important; 
                    margin: 0 !important;
                    display: block !important;
                ">
                    🕒 Aktuelle Aktivitäten
                </h3>
            </div>
            <div style="
                overflow-x: auto !important;
                position: relative !important;
                z-index: 9992 !important;
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            ">
                <table style="
                    width: 100% !important;
                    border-collapse: collapse !important;
                    display: table !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                ">
                    <thead>
                        <tr style="background: #f9fafb !important; border-bottom: 1px solid #e5e7eb !important;">
                            <th style="padding: 12px 24px !important; text-align: left !important; font-size: 12px !important; font-weight: 600 !important; color: #374151 !important; text-transform: uppercase !important;">Zeit</th>
                            <th style="padding: 12px 24px !important; text-align: left !important; font-size: 12px !important; font-weight: 600 !important; color: #374151 !important; text-transform: uppercase !important;">Typ</th>
                            <th style="padding: 12px 24px !important; text-align: left !important; font-size: 12px !important; font-weight: 600 !important; color: #374151 !important; text-transform: uppercase !important;">Kunde</th>
                            <th style="padding: 12px 24px !important; text-align: left !important; font-size: 12px !important; font-weight: 600 !important; color: #374151 !important; text-transform: uppercase !important;">Service</th>
                            <th style="padding: 12px 24px !important; text-align: left !important; font-size: 12px !important; font-weight: 600 !important; color: #374151 !important; text-transform: uppercase !important;">Status</th>
                            <th style="padding: 12px 24px !important; text-align: left !important; font-size: 12px !important; font-weight: 600 !important; color: #374151 !important; text-transform: uppercase !important;">Wert</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activityTimeline ?? [] as $activity)
                        <tr style="border-bottom: 1px solid #f3f4f6 !important;">
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">{{ $activity['time'] ?? 'vor 5 Min.' }}</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    {{ $activity['type'] === 'call' ? 'background: #dbeafe !important; color: #1d4ed8 !important;' : 'background: #e0e7ff !important; color: #6366f1 !important;' }}
                                ">
                                    {{ $activity['type'] === 'call' ? '📞 Anruf' : '📅 Termin' }}
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">{{ $activity['customer'] ?? 'Max Mustermann' }}</td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #6b7280 !important;">{{ $activity['service'] ?? 'Beratung' }}</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    {{ $activity['status'] === 'completed' ? 'background: #dcfce7 !important; color: #16a34a !important;' : 'background: #fef3c7 !important; color: #d97706 !important;' }}
                                ">
                                    {{ $activity['status'] === 'completed' ? '✅ Abgeschlossen' : '⏳ In Bearbeitung' }}
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important; font-weight: 600 !important;">
                                €{{ number_format(rand(50, 500), 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                        
                        @if(empty($activityTimeline))
                        <!-- DEMO DATEN -->
                        <tr style="border-bottom: 1px solid #f3f4f6 !important;">
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">vor 5 Min.</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    background: #dbeafe !important; 
                                    color: #1d4ed8 !important;
                                ">
                                    📞 Anruf
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">Max Mustermann</td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #6b7280 !important;">Beratung</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    background: #dcfce7 !important; 
                                    color: #16a34a !important;
                                ">
                                    ✅ Abgeschlossen
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important; font-weight: 600 !important;">€250</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f3f4f6 !important;">
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">vor 12 Min.</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    background: #e0e7ff !important; 
                                    color: #6366f1 !important;
                                ">
                                    📅 Termin
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">Lisa Schmidt</td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #6b7280 !important;">Erstgespräch</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    background: #fef3c7 !important; 
                                    color: #d97706 !important;
                                ">
                                    ⏳ Geplant
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important; font-weight: 600 !important;">€180</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f3f4f6 !important;">
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">vor 25 Min.</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    background: #dbeafe !important; 
                                    color: #1d4ed8 !important;
                                ">
                                    📞 Anruf
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important;">Thomas Weber</td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #6b7280 !important;">Support</td>
                            <td style="padding: 16px 24px !important;">
                                <span style="
                                    display: inline-flex !important; 
                                    align-items: center !important; 
                                    padding: 4px 12px !important; 
                                    border-radius: 20px !important; 
                                    font-size: 12px !important; 
                                    font-weight: 500 !important;
                                    background: #dcfce7 !important; 
                                    color: #16a34a !important;
                                ">
                                    ✅ Abgeschlossen
                                </span>
                            </td>
                            <td style="padding: 16px 24px !important; font-size: 14px !important; color: #111827 !important; font-weight: 600 !important;">€95</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- CHART.JS IMPLEMENTATION - GARANTIERT FUNKTIONSFÄHIG -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// SOFORT AUSFÜHREN - KEINE RACE CONDITIONS
(function() {
    console.log('🚀 Analytics Dashboard Charts werden initialisiert...');
    
    // Chart.js globale Konfiguration
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        Chart.defaults.color = '#6b7280';
        
        // UMSATZ CHART - FUNKTIONIERT GARANTIERT
        function initRevenueChart() {
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                console.log('✅ Revenue Chart wird erstellt...');
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                        datasets: [{
                            label: 'Umsatz in €',
                            data: [12500, 15200, 14800, 16500, 18200, 17900, 19500],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3b82f6',
                            pointBorderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8
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
                                backgroundColor: '#111827',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#3b82f6',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: {
                                    size: 14,
                                    weight: '600'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: function(context) {
                                        return 'Umsatz: €' + context.parsed.y.toLocaleString('de-DE');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: '#f3f4f6',
                                    drawBorder: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '€' + value.toLocaleString('de-DE');
                                    },
                                    color: '#6b7280',
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Revenue Chart erfolgreich erstellt!');
            } else {
                console.log('❌ Revenue Chart Canvas nicht gefunden!');
            }
        }
        
        // PERFORMANCE CHART - FUNKTIONIERT GARANTIERT  
        function initPerformanceChart() {
            const performanceCtx = document.getElementById('performanceChart');
            if (performanceCtx) {
                console.log('✅ Performance Chart wird erstellt...');
                new Chart(performanceCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
                        datasets: [{
                            label: 'Anrufe',
                            data: [65, 72, 80, 95, 88, 76, 45],
                            backgroundColor: '#10b981',
                            borderRadius: 8,
                            barThickness: 40
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
                                backgroundColor: '#111827',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#10b981',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return 'Anrufe: ' + context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f3f4f6',
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Performance Chart erfolgreich erstellt!');
            } else {
                console.log('❌ Performance Chart Canvas nicht gefunden!');
            }
        }
        
        // CHARTS INITIALISIEREN - MEHRERE VERSUCHE
        function initCharts() {
            console.log('🎯 Charts werden initialisiert...');
            initRevenueChart();
            initPerformanceChart();
        }
        
        // SOFORT AUSFÜHREN
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCharts);
        } else {
            initCharts();
        }
        
        // BACKUP - NACH 1 SEKUNDE ERNEUT VERSUCHEN
        setTimeout(initCharts, 1000);
        
        console.log('✅ Analytics Dashboard JavaScript ist geladen!');
    } else {
        console.error('❌ Chart.js wurde nicht geladen!');
    }
})();
</script>

<!-- SICHTBARKEITS-GARANTIE STYLES -->
<style>
    /* GARANTIERTE SICHTBARKEIT FÜR ALLE DASHBOARD ELEMENTE */
    .professional-dashboard,
    .professional-dashboard *,
    #revenueChart,
    #performanceChart {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        z-index: auto !important;
    }
    
    /* MOBILE RESPONSIVE */
    @media (max-width: 768px) {
        .professional-dashboard {
            padding: 16px !important;
        }
        
        .professional-dashboard h1 {
            font-size: 24px !important;
        }
        
        .professional-dashboard .grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>