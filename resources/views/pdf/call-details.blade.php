<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anrufdetails - {{ $call->id }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        
        .header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        h1 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .subtitle {
            color: #6b7280;
            font-size: 16px;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .info-value {
            font-size: 16px;
            color: #111827;
            font-weight: 500;
        }
        
        .sentiment {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sentiment-positive {
            background: #d1fae5;
            color: #065f46;
        }
        
        .sentiment-negative {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .sentiment-neutral {
            background: #f3f4f6;
            color: #374151;
        }
        
        .summary-text {
            line-height: 1.8;
            color: #4b5563;
            white-space: pre-wrap;
        }
        
        .transcript-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .financial-metrics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .metric {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .metric-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        @media screen and (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .financial-metrics {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Anrufdetails</h1>
        <div class="subtitle">{{ $companyName }}@if($branchName) - {{ $branchName }}@endif</div>
    </div>
    
    <!-- Anrufinformationen -->
    <div class="section">
        <div class="section-title">Anrufinformationen</div>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Anrufer</span>
                <span class="info-value">{{ $customerName }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Telefonnummer</span>
                <span class="info-value">{{ $phoneNumber }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Datum & Zeit</span>
                <span class="info-value">{{ $callDate }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Dauer</span>
                <span class="info-value">{{ $duration }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Stimmung</span>
                <span class="info-value">
                    <span class="sentiment sentiment-{{ $sentiment }}">
                        {{ ucfirst($sentiment) }}
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value">{{ ucfirst($status) }}</span>
            </div>
        </div>
    </div>
    
    <!-- Finanzdaten -->
    @if(isset($financials) && $financials)
    <div class="section">
        <div class="section-title">Finanzdaten</div>
        <div class="financial-metrics">
            <div class="metric">
                <div class="metric-value">{{ number_format($financials['cost'], 2) }}€</div>
                <div class="metric-label">Kosten</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ number_format($financials['revenue'], 2) }}€</div>
                <div class="metric-label">Umsatz</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ number_format($financials['profit'], 2) }}€</div>
                <div class="metric-label">Gewinn</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ number_format($financials['margin'], 0) }}%</div>
                <div class="metric-label">Marge</div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Zusammenfassung -->
    <div class="section">
        <div class="section-title">Zusammenfassung</div>
        <div class="summary-text">{{ $summary }}</div>
    </div>
    
    <!-- Transkript -->
    <div class="section">
        <div class="section-title">Transkript</div>
        <div class="transcript-box">
            <div class="summary-text">{{ $transcript }}</div>
        </div>
    </div>
    
    <div class="footer">
        <p>Generiert am {{ now()->format('d.m.Y H:i') }} Uhr</p>
        <p>© {{ date('Y') }} {{ $companyName }}</p>
    </div>
    
    <script>
        // Auto-trigger print dialog when opened in a new window
        if (window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
</body>
</html>