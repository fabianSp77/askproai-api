<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anruf {{ $call->id }} - {{ $call->created_at->format('d.m.Y H:i') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: #1f2937;
        }
        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
        }
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
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            padding: 8px 0;
            font-weight: 600;
            color: #6b7280;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            width: 60%;
            padding: 8px 0;
            color: #1f2937;
            vertical-align: top;
        }
        .urgency-high {
            color: #dc2626;
            font-weight: bold;
        }
        .urgency-medium {
            color: #f59e0b;
            font-weight: bold;
        }
        .urgency-low {
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-new {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-in_progress {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-requires_action {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-callback_scheduled {
            background-color: #ede9fe;
            color: #5b21b6;
        }
        .summary-box {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .transcript {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
        }
        .transcript-message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 6px;
        }
        .transcript-agent {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
        }
        .transcript-customer {
            background-color: #dbeafe;
            border: 1px solid #93c5fd;
        }
        .transcript-role {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }
        .note {
            background-color: #fef3c7;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #fcd34d;
        }
        .note-header {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        @page {
            margin: 20mm;
        }
    </style>
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
    @endphp
    
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
            Datum: {{ $call->created_at->format('d.m.Y H:i') }} | 
            Dauer: {{ gmdate('i:s', $call->duration_sec ?? 0) }} Minuten
        </div>
    </div>

    {{-- Basic Information --}}
    <div class="section">
        <h2 class="section-title">Grundinformationen</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    @php
                        $status = optional($call->callPortalData)->status ?? 'new';
                        $statusClass = 'status-' . $status;
                        $statusDisplay = match($status) {
                            'new' => 'Neu',
                            'in_progress' => 'In Bearbeitung',
                            'requires_action' => 'Aktion erforderlich',
                            'completed' => 'Abgeschlossen',
                            'callback_scheduled' => 'Rückruf geplant',
                            default => ucfirst($status)
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusDisplay }}</span>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Telefonnummer:</div>
                <div class="info-value">{{ $phoneNumber }}</div>
            </div>
            
            @if($call->branch)
            <div class="info-row">
                <div class="info-label">Filiale:</div>
                <div class="info-value">{{ $call->branch->name }}</div>
            </div>
            @endif
            
            @if($call->urgency_level || ($customerData['urgency'] ?? null))
            <div class="info-row">
                <div class="info-label">Dringlichkeit:</div>
                <div class="info-value">
                    @php
                        $urgency = $call->urgency_level ?? $customerData['urgency'] ?? null;
                        if ($urgency) {
                            $urgencyClass = match(strtolower($urgency)) {
                                'high', 'hoch' => 'urgency-high',
                                'medium', 'mittel' => 'urgency-medium',
                                default => 'urgency-low'
                            };
                            $urgencyText = match(strtolower($urgency)) {
                                'high' => 'Hoch',
                                'medium' => 'Mittel',
                                'low' => 'Niedrig',
                                default => ucfirst($urgency)
                            };
                        }
                    @endphp
                    @if($urgency)
                        <span class="{{ $urgencyClass }}">{{ $urgencyText }}</span>
                    @endif
                </div>
            </div>
            @endif
            
            @if($showCosts && $call->cost)
            <div class="info-row">
                <div class="info-label">Kosten:</div>
                <div class="info-value"><strong>{{ number_format($call->cost, 2, ',', '.') }} €</strong></div>
            </div>
            @endif
            
            <div class="info-row">
                <div class="info-label">Bearbeiter:</div>
                <div class="info-value">{{ optional($call->callPortalData)->assignedTo->name ?? 'Nicht zugewiesen' }}</div>
            </div>
        </div>
    </div>

    {{-- Customer Information --}}
    @if(!empty($customerData))
    <div class="section">
        <h2 class="section-title">Kundendaten</h2>
        <div class="info-grid">
            @if(!empty($customerData['full_name']) || $call->extracted_name)
            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value">{{ $customerData['full_name'] ?? $call->extracted_name }}</div>
            </div>
            @endif
            
            @if(!empty($customerData['company']))
            <div class="info-row">
                <div class="info-label">Firma:</div>
                <div class="info-value">{{ $customerData['company'] }}</div>
            </div>
            @endif
            
            @if(!empty($customerData['email']) || $call->extracted_email)
            <div class="info-row">
                <div class="info-label">E-Mail:</div>
                <div class="info-value">{{ $customerData['email'] ?? $call->extracted_email }}</div>
            </div>
            @endif
            
            @if(!empty($customerData['customer_number']))
            <div class="info-row">
                <div class="info-label">Kundennummer:</div>
                <div class="info-value">{{ $customerData['customer_number'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Customer Request --}}
    @if($call->reason_for_visit || ($customerData['request'] ?? null))
    <div class="section">
        <h2 class="section-title">Kundenanliegen</h2>
        <div class="summary-box">
            {{ $call->reason_for_visit ?? $customerData['request'] ?? '' }}
            
            @if($call->appointment_requested)
            <p style="margin-top: 10px; color: #d97706;">
                <strong>⚠ Kunde hat einen Terminwunsch geäußert</strong>
            </p>
            @endif
        </div>
    </div>
    @endif

    {{-- Summary --}}
    @if($call->summary)
    <div class="section">
        <h2 class="section-title">Zusammenfassung</h2>
        <div class="summary-box">
            {{ $call->summary }}
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if(optional($call->callPortalData)->internal_notes)
    <div class="section">
        <h2 class="section-title">Interne Notizen</h2>
        <div class="note">
            {{ $call->callPortalData->internal_notes }}
        </div>
    </div>
    @endif
    
    @if($call->callNotes && $call->callNotes->count() > 0)
    <div class="section">
        <h2 class="section-title">Notizen</h2>
        @foreach($call->callNotes as $note)
        <div class="note">
            <div class="note-header">
                {{ optional($note->user)->name ?? 'System' }} - 
                {{ $note->created_at ? $note->created_at->format('d.m.Y H:i') : '' }}
            </div>
            {{ $note->content }}
        </div>
        @endforeach
    </div>
    @endif

    {{-- Transcript (if page space allows) --}}
    @if($call->transcript)
    <div class="section">
        <h2 class="section-title">Gesprächsverlauf</h2>
        <div class="transcript">
            @php
                $transcript = is_string($call->transcript) ? json_decode($call->transcript, true) : $call->transcript;
            @endphp
            @if(is_array($transcript))
                @foreach(array_slice($transcript, 0, 20) as $message) {{-- Limit to first 20 messages for PDF --}}
                    @if(isset($message['role']) && isset($message['content']))
                    <div class="transcript-message {{ $message['role'] === 'agent' ? 'transcript-agent' : 'transcript-customer' }}">
                        <div class="transcript-role">
                            {{ $message['role'] === 'agent' ? 'Agent' : 'Kunde' }}
                        </div>
                        {{ $message['content'] }}
                    </div>
                    @endif
                @endforeach
                @if(count($transcript) > 20)
                    <p style="text-align: center; color: #6b7280; margin-top: 10px;">
                        ... und {{ count($transcript) - 20 }} weitere Nachrichten
                    </p>
                @endif
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>
            Exportiert am {{ now()->format('d.m.Y \u\m H:i') }} Uhr<br>
            © {{ date('Y') }} AskProAI - Alle Rechte vorbehalten
        </p>
    </div>
</body>
</html>