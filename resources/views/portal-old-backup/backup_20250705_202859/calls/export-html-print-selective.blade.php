<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $filename }}</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            background: white;
        }
        
        /* Print styles */
        @media print {
            body {
                margin: 0;
                color: #000;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            a {
                text-decoration: none;
                color: inherit;
            }
        }
        
        @page {
            size: A4;
            margin: 15mm 20mm;
        }
        
        /* Layout */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        @media print {
            .container {
                max-width: 100%;
                padding: 0;
            }
        }
        
        /* Header */
        .header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #1f2937;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-size: 13px;
        }
        
        /* Sections */
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        /* Info grid */
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label,
        .info-value {
            display: table-cell;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-label {
            width: 180px;
            font-weight: 600;
            color: #6b7280;
        }
        
        .info-value {
            color: #1f2937;
        }
        
        /* Status badges */
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-new { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .status-requires_action { background-color: #fee2e2; color: #991b1b; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        
        /* Transcript */
        .transcript {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .transcript-entry {
            margin-bottom: 12px;
            padding: 10px;
            border-radius: 4px;
            background-color: white;
            border: 1px solid #e5e7eb;
        }
        
        .transcript-customer {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
        }
        
        @media print {
            .transcript-customer {
                background-color: #eff6ff;
            }
        }
        
        .transcript-role {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        /* Notes */
        .note {
            background-color: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .note-header {
            font-size: 11px;
            color: #92400e;
            margin-bottom: 5px;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        
        /* Print button */
        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        
        @media print {
            .print-actions {
                display: none;
            }
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #4b5563;
        }
    </style>
    
    @if($printMode)
    <script>
        // Auto-trigger print dialog
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
        
        // Handle print completion
        window.addEventListener('afterprint', function() {
            // Optional: close window after printing
            // window.close();
        });
    </script>
    @endif
</head>
<body>
    @php
        $companyName = $call->company->name ?? 'Unternehmen';
        // Safely decode metadata
        $metadata = [];
        if ($call->metadata) {
            if (is_string($call->metadata)) {
                $metadata = json_decode($call->metadata, true) ?? [];
            } elseif (is_array($call->metadata)) {
                $metadata = $call->metadata;
            }
        }
        $customerData = $metadata['customer_data'] ?? [];
        
        $customerName = $call->extracted_name ?? 
                       ($call->customer ? $call->customer->name : null) ?? 
                       ($customerData['full_name'] ?? null);
        $phoneNumber = $call->phone_number ?? $call->from_number;
        
        // Field mappings for display
        $fieldData = [
            'date' => ['label' => 'Datum', 'value' => $call->created_at->format('d.m.Y')],
            'time' => ['label' => 'Uhrzeit', 'value' => $call->created_at->format('H:i')],
            'phone_number' => ['label' => 'Telefonnummer', 'value' => $phoneNumber],
            'customer_name' => ['label' => 'Kunde', 'value' => $customerName ?? 'Unbekannt'],
            'customer_email' => ['label' => 'E-Mail', 'value' => $call->customer ? $call->customer->email : ''],
            'customer_company' => ['label' => 'Firma', 'value' => $customerData['company'] ?? ($call->customer ? $call->customer->company_name : '')],
            'customer_number' => ['label' => 'Kundennummer', 'value' => $call->customer ? $call->customer->customer_number : ''],
            'summary' => ['label' => 'Zusammenfassung', 'value' => $call->summary ?? ''],
            'reason' => ['label' => 'Anliegen', 'value' => $call->reason_for_visit ?? $call->summary ?? ''],
            'duration' => ['label' => 'Dauer', 'value' => gmdate('i:s', $call->duration_sec ?? 0) . ' Minuten'],
            'status' => ['label' => 'Status', 'value' => optional($call->callPortalData)->status ?? 'new', 'type' => 'status'],
            'assigned_to' => ['label' => 'Zugewiesen an', 'value' => optional($call->callPortalData)->assignedTo->name ?? 'Nicht zugewiesen'],
            'branch' => ['label' => 'Filiale', 'value' => optional($call->branch)->name ?? ''],
            'urgency' => ['label' => 'Dringlichkeit', 'value' => $call->urgency_level ?? ($customerData['urgency'] ?? '')],
            'cost' => ['label' => 'Kosten', 'value' => $showCosts && $call->duration_sec ? number_format($call->total_cost ?? 0, 2, ',', '.') . ' €' : ''],
            'price_per_minute' => ['label' => 'Preis pro Minute', 'value' => $showCosts ? number_format($call->price_per_minute ?? 0.39, 2, ',', '.') . ' €' : ''],
        ];
        
        // Group fields by category
        $fieldGroups = [
            'basic' => ['date', 'time', 'phone_number', 'duration', 'status'],
            'customer' => ['customer_name', 'customer_email', 'customer_company', 'customer_number'],
            'content' => ['summary', 'reason', 'notes', 'transcript'],
            'administrative' => ['assigned_to', 'branch', 'urgency'],
            'financial' => ['cost', 'price_per_minute'],
        ];
    @endphp
    
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Drucken / PDF speichern</button>
        <button onclick="window.close()" class="btn btn-secondary">Schließen</button>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>
                @if($customerName)
                    {{ $companyName }} wurde angerufen von {{ $customerName }}
                @else
                    {{ $companyName }} - Anruf von {{ $phoneNumber }}
                @endif
            </h1>
            <div class="subtitle">
                Anruf-ID: {{ $call->id }} | 
                @if(in_array('date', $selectedFields))
                    Datum: {{ $call->created_at->format('d.m.Y H:i') }} | 
                @endif
                @if(in_array('duration', $selectedFields))
                    Dauer: {{ gmdate('i:s', $call->duration_sec ?? 0) }} Minuten
                @endif
            </div>
        </div>

        {{-- Display selected fields in groups --}}
        @foreach($fieldGroups as $groupName => $groupFields)
            @php
                $hasFieldsInGroup = false;
                foreach($groupFields as $field) {
                    if(in_array($field, $selectedFields) && isset($fieldData[$field])) {
                        $hasFieldsInGroup = true;
                        break;
                    }
                }
            @endphp
            
            @if($hasFieldsInGroup)
                <div class="section">
                    <h2 class="section-title">
                        @switch($groupName)
                            @case('basic')
                                Grundinformationen
                                @break
                            @case('customer')
                                Kundendaten
                                @break
                            @case('content')
                                Gesprächsinhalt
                                @break
                            @case('administrative')
                                Administrative Daten
                                @break
                            @case('financial')
                                Finanzdaten
                                @break
                        @endswitch
                    </h2>
                    <div class="info-grid">
                        @foreach($groupFields as $field)
                            @if(in_array($field, $selectedFields) && isset($fieldData[$field]) && $fieldData[$field]['value'])
                                @if($field == 'notes' && in_array('notes', $selectedFields))
                                    {{-- Special handling for notes --}}
                                    <div class="info-row">
                                        <div class="info-label">Notizen:</div>
                                        <div class="info-value">
                                            @if($call->notes && $call->notes->count() > 0)
                                                @foreach($call->notes as $note)
                                                    <div class="note">
                                                        <div class="note-header">
                                                            {{ $note->user->name ?? 'System' }} - {{ $note->created_at->format('d.m.Y H:i') }}
                                                        </div>
                                                        {{ $note->content }}
                                                    </div>
                                                @endforeach
                                            @else
                                                <em>Keine Notizen vorhanden</em>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($field == 'transcript' && in_array('transcript', $selectedFields))
                                    {{-- Special handling for transcript --}}
                                    <div class="info-row">
                                        <div class="info-label">Transkript:</div>
                                        <div class="info-value">
                                            @if($call->transcript)
                                                <div class="transcript">
                                                    @php
                                                        $lines = explode("\n", $call->transcript);
                                                        foreach($lines as $line) {
                                                            $isCustomer = stripos($line, 'customer:') === 0 || stripos($line, 'kunde:') === 0;
                                                            $role = $isCustomer ? 'Kunde' : 'Agent';
                                                            $content = preg_replace('/^(customer|kunde|agent):\s*/i', '', $line);
                                                    @endphp
                                                    @if(trim($content))
                                                        <div class="transcript-entry {{ $isCustomer ? 'transcript-customer' : '' }}">
                                                            <div class="transcript-role">{{ $role }}</div>
                                                            {{ $content }}
                                                        </div>
                                                    @endif
                                                    @php } @endphp
                                                </div>
                                            @else
                                                <em>Kein Transkript verfügbar</em>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="info-row">
                                        <div class="info-label">{{ $fieldData[$field]['label'] }}:</div>
                                        <div class="info-value">
                                            @if(isset($fieldData[$field]['type']) && $fieldData[$field]['type'] == 'status')
                                                @php
                                                    $status = $fieldData[$field]['value'];
                                                    $statusClass = 'status-' . $status;
                                                    $statusDisplay = match($status) {
                                                        'new' => 'Neu',
                                                        'in_progress' => 'In Bearbeitung',
                                                        'requires_action' => 'Aktion erforderlich',
                                                        'completed' => 'Abgeschlossen',
                                                        default => ucfirst($status)
                                                    };
                                                @endphp
                                                <span class="status {{ $statusClass }}">{{ $statusDisplay }}</span>
                                            @else
                                                {{ $fieldData[$field]['value'] }}
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        <div class="footer">
            <p>Exportiert am {{ now()->format('d.m.Y H:i') }} Uhr</p>
            <p>{{ $companyName }} - Vertrauliche Informationen</p>
        </div>
    </div>
</body>
</html>