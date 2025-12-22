<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support-Ticket Backup</title>
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color:#f3f4f6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="background-color:#ffffff; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.07);">

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 1: QUICK INFO (TOP) --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    @php
                        $priorityStyles = [
                            'critical' => ['bg' => '#dc2626', 'label' => 'KRITISCH'],
                            'high' => ['bg' => '#ea580c', 'label' => 'HOCH'],
                            'normal' => ['bg' => '#2563eb', 'label' => 'NORMAL'],
                            'low' => ['bg' => '#16a34a', 'label' => 'NIEDRIG'],
                        ];
                        $pStyle = $priorityStyles[$case->priority] ?? $priorityStyles['normal'];

                        $statusLabels = [
                            'new' => 'Neu',
                            'open' => 'Offen',
                            'pending' => 'Wartend',
                            'in_progress' => 'In Bearbeitung',
                            'resolved' => 'Gelöst',
                            'closed' => 'Geschlossen',
                        ];

                        $typeLabels = [
                            'incident' => 'Störung',
                            'request' => 'Anfrage',
                            'inquiry' => 'Rückfrage',
                        ];

                        $meta = $case->ai_metadata ?? [];
                    @endphp

                    <!-- Priority Header Bar -->
                    <tr>
                        <td style="background-color:{{ $pStyle['bg'] }}; padding:14px 24px; border-radius:12px 12px 0 0;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color:#ffffff; font-size:13px; font-weight:700; letter-spacing:0.5px;">
                                        {{ $pStyle['label'] }} PRIORITÄT
                                    </td>
                                    <td align="right" style="color:rgba(255,255,255,0.85); font-size:12px;">
                                        {{ $case->created_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Ticket ID Hero -->
                    <tr>
                        <td align="center" style="padding:28px 24px 12px;">
                            <div style="font-family:'SF Mono', Monaco, 'Courier New', monospace; font-size:32px; font-weight:700; color:#111827; letter-spacing:1px;">
                                {{ $case->formatted_id }}
                            </div>
                            <div style="color:#6b7280; font-size:12px; margin-top:4px;">Ticket-Nummer</div>
                        </td>
                    </tr>

                    <!-- Status Badges -->
                    <tr>
                        <td align="center" style="padding:0 24px 20px;">
                            <span style="display:inline-block; background-color:#f3f4f6; color:#374151; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; margin:3px;">
                                {{ $statusLabels[$case->status] ?? $case->status }}
                            </span>
                            <span style="display:inline-block; background-color:#fef3c7; color:#92400e; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; margin:3px;">
                                {{ $typeLabels[$case->case_type] ?? $case->case_type }}
                            </span>
                            @if($case->category)
                            <span style="display:inline-block; background-color:#dbeafe; color:#1e40af; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; margin:3px;">
                                {{ $case->category->name }}
                            </span>
                            @endif
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:0 24px;">
                            <div style="border-top:1px solid #e5e7eb;"></div>
                        </td>
                    </tr>

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 2: CONTACT INFO --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    @php
                        $hasContact = !empty($meta['customer_name']) || !empty($meta['customer_phone']) || !empty($meta['customer_email']) || !empty($meta['customer_location']);
                    @endphp

                    @if($hasContact)
                    <tr>
                        <td style="padding:20px 24px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <div style="color:#1d4ed8; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">
                                            Kontaktdaten
                                        </div>
                                        <table width="100%" cellspacing="0" cellpadding="0">
                                            @if(!empty($meta['customer_name']))
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-bottom:8px; width:100px;">Name</td>
                                                <td style="color:#111827; font-size:13px; font-weight:500; padding-bottom:8px;">{{ $meta['customer_name'] }}</td>
                                            </tr>
                                            @endif
                                            @if(!empty($meta['customer_phone']))
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-bottom:8px; width:100px;">Telefon</td>
                                                <td style="padding-bottom:8px;">
                                                    <a href="tel:{{ $meta['customer_phone'] }}" style="color:#2563eb; font-size:13px; font-weight:500; text-decoration:none;">{{ $meta['customer_phone'] }}</a>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(!empty($meta['customer_email']))
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-bottom:8px; width:100px;">E-Mail</td>
                                                <td style="padding-bottom:8px;">
                                                    <a href="mailto:{{ $meta['customer_email'] }}" style="color:#2563eb; font-size:13px; font-weight:500; text-decoration:none;">{{ $meta['customer_email'] }}</a>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(!empty($meta['customer_location']))
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; width:100px;">Standort</td>
                                                <td style="color:#111827; font-size:13px; font-weight:500;">{{ $meta['customer_location'] }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 3: ISSUE DETAILS --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    <tr>
                        <td style="padding:0 24px 20px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#fff7ed; border:1px solid #fed7aa; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <div style="color:#c2410c; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">
                                            Problembeschreibung
                                        </div>

                                        <!-- Subject as Title -->
                                        <div style="color:#111827; font-size:16px; font-weight:600; margin-bottom:12px;">
                                            {{ $case->subject }}
                                        </div>

                                        <!-- Description (only if different from subject) -->
                                        @if($case->description && trim($case->description) !== trim($case->subject))
                                        <div style="background-color:#ffffff; border-radius:6px; padding:12px; color:#374151; font-size:13px; line-height:1.6;">
                                            {{ $case->description }}
                                        </div>
                                        @endif

                                        <!-- Additional Info -->
                                        @if(!empty($meta['problem_since']) || (!empty($meta['others_affected']) && $meta['others_affected'] !== 'nein' && $meta['others_affected'] !== false))
                                        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top:12px;">
                                            @if(!empty($meta['problem_since']))
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-top:8px; width:120px;">Problem seit</td>
                                                <td style="color:#111827; font-size:13px; padding-top:8px;">{{ $meta['problem_since'] }}</td>
                                            </tr>
                                            @endif
                                            @if(!empty($meta['others_affected']) && $meta['others_affected'] !== 'nein' && $meta['others_affected'] !== false)
                                            <tr>
                                                <td style="color:#6b7280; font-size:12px; padding-top:8px; width:120px;">Betroffene</td>
                                                <td style="padding-top:8px;">
                                                    <span style="display:inline-block; background-color:#fecaca; color:#b91c1c; padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600;">
                                                        Mehrere Mitarbeiter betroffen
                                                    </span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 4: AI SUMMARY (if enabled) --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    @if(($includeSummary ?? false) && !empty($jsonData['zusammenfassung'] ?? null))
                    <tr>
                        <td style="padding:0 24px 20px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <div style="color:#166534; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">
                                            KI-Zusammenfassung
                                        </div>
                                        <div style="color:#374151; font-size:13px; line-height:1.6;">
                                            {{ $jsonData['zusammenfassung'] }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 5: TRANSCRIPT (if enabled) --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    @if(($includeTranscript ?? false) && !empty($chatTranscript ?? []))
                    <tr>
                        <td style="padding:0 24px 20px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#faf5ff; border:1px solid #d8b4fe; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <div style="color:#7c3aed; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">
                                            Gesprächsverlauf
                                            @if($transcriptTruncated ?? false)
                                            <span style="font-weight:400; font-size:10px; color:#9ca3af;">(gekürzt)</span>
                                            @endif
                                        </div>

                                        <div style="background-color:#ffffff; border-radius:6px; padding:12px; max-height:400px; overflow-y:auto;">
                                            @foreach($chatTranscript as $segment)
                                                @php
                                                    $isAgent = $segment['role'] === 'agent';
                                                    $isSystem = $segment['role'] === 'system';
                                                    $bgColor = $isSystem ? '#fef3c7' : ($isAgent ? '#eff6ff' : '#f3f4f6');
                                                    $textColor = $isSystem ? '#92400e' : '#374151';
                                                    $roleLabel = $isSystem ? 'System' : ($isAgent ? 'Support' : 'Kunde');
                                                    $roleColor = $isSystem ? '#92400e' : ($isAgent ? '#1d4ed8' : '#047857');
                                                @endphp
                                                <div style="background-color:{{ $bgColor }}; border-radius:6px; padding:10px 12px; margin-bottom:8px;">
                                                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                                        <span style="color:{{ $roleColor }}; font-size:11px; font-weight:600;">{{ $roleLabel }}</span>
                                                        @if($segment['time'] ?? null)
                                                        <span style="color:#9ca3af; font-size:10px; font-family:'SF Mono', Monaco, monospace;">{{ $segment['time'] }}</span>
                                                        @endif
                                                    </div>
                                                    <div style="color:{{ $textColor }}; font-size:12px; line-height:1.5;">{{ $segment['text'] }}</div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($transcriptTruncated ?? false)
                                        <div style="color:#9ca3af; font-size:10px; margin-top:8px; text-align:center;">
                                            {{ number_format($originalTranscriptLength ?? 0) }} Zeichen gesamt
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 6: AUDIO (if enabled) --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    @if(($audioOption ?? 'none') === 'link' && !empty($audioUrl ?? null))
                    <tr>
                        <td style="padding:0 24px 20px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#fef2f2; border:1px solid #fecaca; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px; text-align:center;">
                                        <div style="color:#b91c1c; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">
                                            Aufnahme
                                        </div>
                                        <a href="{{ $audioUrl }}" style="display:inline-block; background-color:#b91c1c; color:#ffffff; padding:12px 24px; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none;">
                                            Aufnahme anhören
                                        </a>
                                        <div style="color:#9ca3af; font-size:10px; margin-top:8px;">
                                            Link gültig für 24 Stunden
                                            @if($audioSizeExceeded ?? false)
                                            <br><span style="color:#ea580c;">(Datei zu groß für Anhang)</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 7: ACTION BUTTON --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    <tr>
                        <td align="center" style="padding:0 24px 24px;">
                            <a href="{{ config('app.url') }}/admin/service-cases/{{ $case->id }}" style="display:inline-block; background-color:#2563eb; color:#ffffff; padding:14px 32px; border-radius:8px; font-size:14px; font-weight:600; text-decoration:none;">
                                Ticket bearbeiten
                            </a>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:0 24px;">
                            <div style="border-top:1px solid #e5e7eb;"></div>
                        </td>
                    </tr>

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- SECTION 5: TECHNICAL DATA (JSON) --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    <tr>
                        <td style="padding:20px 24px;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <div style="color:#6b7280; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px;">
                                            Technische Daten (Backup)
                                        </div>

                                        <!-- Reference IDs -->
                                        <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:12px;">
                                            <tr>
                                                <td style="color:#6b7280; font-size:11px; padding-bottom:4px; width:120px;">Interne ID</td>
                                                <td style="color:#374151; font-size:11px; font-family:'SF Mono', Monaco, monospace; padding-bottom:4px;">{{ $case->id }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color:#6b7280; font-size:11px; padding-bottom:4px; width:120px;">Kategorie-ID</td>
                                                <td style="color:#374151; font-size:11px; font-family:'SF Mono', Monaco, monospace; padding-bottom:4px;">{{ $case->category_id }}</td>
                                            </tr>
                                            @if(!empty($meta['retell_call_id']) || $case->call)
                                            <tr>
                                                <td style="color:#6b7280; font-size:11px; width:120px;">Anruf-Referenz</td>
                                                <td style="color:#374151; font-size:11px; font-family:'SF Mono', Monaco, monospace;">{{ Str::limit($meta['retell_call_id'] ?? $case->call?->retell_call_id ?? '-', 20) }}</td>
                                            </tr>
                                            @endif
                                        </table>

                                        <!-- JSON Data Block -->
                                        <div style="background-color:#1f2937; border-radius:6px; padding:12px; overflow-x:auto;">
                                            <pre style="margin:0; color:#e5e7eb; font-size:10px; font-family:'SF Mono', Monaco, 'Courier New', monospace; white-space:pre-wrap; word-wrap:break-word;">{{ json_encode($jsonData ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>

                                        <div style="color:#9ca3af; font-size:10px; margin-top:8px; text-align:center;">
                                            Vollständige Daten sind als JSON-Datei angehängt
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ═══════════════════════════════════════════════════════════════ --}}
                    {{-- FOOTER --}}
                    {{-- ═══════════════════════════════════════════════════════════════ --}}

                    <tr>
                        <td style="background-color:#f9fafb; padding:16px 24px; border-radius:0 0 12px 12px; border-top:1px solid #e5e7eb;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color:#9ca3af; font-size:11px;">
                                        Gesendet: {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                    </td>
                                    <td align="right" style="color:#9ca3af; font-size:11px;">
                                        {{ $case->company->name ?? config('app.name') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!-- Footer Note -->
                <table width="640" cellspacing="0" cellpadding="0" style="margin-top:16px;">
                    <tr>
                        <td align="center" style="color:#9ca3af; font-size:10px;">
                            Automatische Backup-Benachrichtigung • Diese E-Mail dient der Dokumentation
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
