<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <title>{{ $isInternal ? 'Neues Support-Ticket' : 'Ihre Anfrage wurde erfasst' }} - {{ $case->formatted_id }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        /* Reset styles */
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }

        /* Mobile responsive */
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .fluid { max-width: 100% !important; height: auto !important; margin-left: auto !important; margin-right: auto !important; }
            .stack-column, .stack-column-center { display: block !important; width: 100% !important; max-width: 100% !important; direction: ltr !important; }
            .stack-column-center { text-align: center !important; }
            .center-on-narrow { text-align: center !important; display: block !important; margin-left: auto !important; margin-right: auto !important; float: none !important; }
            table.center-on-narrow { display: inline-block !important; }
            .info-card { margin-bottom: 16px !important; }
            .button-td { padding: 0 20px !important; }
            .button-a { width: 100% !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <!-- Preheader Text -->
    <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        @if($isInternal)
            {{ $case->formatted_id }} | {{ $priorityLabels[$case->priority] ?? 'Normal' }} | {{ $case->subject }}
        @else
            Ihre Ticket-Nummer: {{ $case->formatted_id }} - Wir bearbeiten Ihr Anliegen.
        @endif
    </div>

    @php
        // Priority configuration
        $priorityColors = [
            'critical' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'border' => '#DC2626', 'icon' => '!!!'],
            'high' => ['bg' => '#FEF3C7', 'text' => '#D97706', 'border' => '#F59E0B', 'icon' => '!!'],
            'normal' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'border' => '#3B82F6', 'icon' => '!'],
            'low' => ['bg' => '#F3F4F6', 'text' => '#4B5563', 'border' => '#6B7280', 'icon' => '-'],
        ];
        $pColor = $priorityColors[$case->priority] ?? $priorityColors['normal'];

        // Status configuration
        $statusColors = [
            'new' => ['bg' => '#F3F4F6', 'text' => '#374151'],
            'open' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
            'pending' => ['bg' => '#FEF3C7', 'text' => '#D97706'],
            'resolved' => ['bg' => '#D1FAE5', 'text' => '#059669'],
            'closed' => ['bg' => '#E0E7FF', 'text' => '#4338CA'],
        ];
        $sColor = $statusColors[$case->status] ?? $statusColors['new'];

        // Type configuration
        $typeColors = [
            'incident' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'icon' => '!'],
            'request' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'icon' => '+'],
            'inquiry' => ['bg' => '#F3E8FF', 'text' => '#7C3AED', 'icon' => '?'],
        ];
        $tColor = $typeColors[$case->case_type] ?? $typeColors['request'];

        // German translations
        $priorityLabels = ['critical' => 'Kritisch', 'high' => 'Hoch', 'normal' => 'Normal', 'low' => 'Niedrig'];
        $statusLabels = ['new' => 'Neu', 'open' => 'Offen', 'pending' => 'Wartend', 'resolved' => 'Geloest', 'closed' => 'Geschlossen'];
        $typeLabels = ['incident' => 'Stoerung', 'request' => 'Anfrage', 'inquiry' => 'Rueckfrage'];

        // SLA calculations
        $isResponseOverdue = $case->sla_response_due_at && now()->isAfter($case->sla_response_due_at) && !in_array($case->status, ['resolved', 'closed']);
        $isResolutionOverdue = $case->sla_resolution_due_at && now()->isAfter($case->sla_resolution_due_at) && !in_array($case->status, ['resolved', 'closed']);
        $isAtRisk = $case->sla_response_due_at && !$isResponseOverdue && now()->diffInMinutes($case->sla_response_due_at, false) < 30 && now()->diffInMinutes($case->sla_response_due_at, false) > 0;

        // Fallback SLA based on priority if not set
        if (!$case->sla_response_due_at && $case->priority === 'critical') {
            $isResponseOverdue = $case->created_at->diffInMinutes(now()) > 60;
        } elseif (!$case->sla_response_due_at && $case->priority === 'high') {
            $isResponseOverdue = $case->created_at->diffInMinutes(now()) > 240;
        }

        // Extract ai_metadata fields with safe defaults
        $aiMeta = $case->ai_metadata ?? [];
        $customerName = $aiMeta['customer_name'] ?? $case->customer?->name ?? 'Unbekannt';
        $customerPhone = $aiMeta['customer_phone'] ?? $case->customer?->phone ?? '-';
        $customerEmail = $aiMeta['customer_email'] ?? $case->customer?->email ?? null;
        $customerLocation = $aiMeta['customer_location'] ?? null;
        $othersAffected = $aiMeta['others_affected'] ?? null;
        $problemSince = $aiMeta['problem_since'] ?? null;
        $retellCallId = $aiMeta['retell_call_id'] ?? $case->call?->retell_call_id ?? null;
        $aiSummary = $aiMeta['ai_summary'] ?? $case->ai_summary ?? null;
        $confidence = $aiMeta['confidence'] ?? null;

        // Servicenummer / Angerufene Nummer Details
        $calledPhone = $case->call?->phoneNumber;
        $serviceNumber = $calledPhone?->formatted_number ?? $case->call?->to_number ?? null;
        $calledCompanyName = $calledPhone?->company?->name ?? null;
        $calledBranchName = $calledPhone?->branch?->name ?? null;
        $calledBranchPhone = $calledPhone?->branch?->phone_number ?? null;
        $callDuration = $case->call?->duration_formatted;

        // Format: "Berlin (+49 30 123456)" oder nur "AskPro GmbH"
        $receiverDisplay = null;
        if ($calledBranchName && $calledBranchPhone) {
            $receiverDisplay = $calledBranchName . ' (' . $calledBranchPhone . ')';
        } elseif ($calledBranchName && $calledCompanyName) {
            $receiverDisplay = $calledBranchName . ' (' . $calledCompanyName . ')';
        } elseif ($calledBranchName) {
            $receiverDisplay = $calledBranchName;
        } elseif ($calledCompanyName) {
            $receiverDisplay = $calledCompanyName;
        }
    @endphp

    <!-- Email Container -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">

                    @if($isInternal)
                    {{-- ================================================================ --}}
                    {{-- INTERNAL VERSION - Full technical details for IT support staff  --}}
                    {{-- ================================================================ --}}

                    {{-- Priority Header Bar --}}
                    <tr>
                        <td style="background-color: {{ $pColor['bg'] }}; border-left: 5px solid {{ $pColor['border'] }}; padding: 16px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: {{ $pColor['text'] }}; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        {{ $pColor['icon'] }} {{ $priorityLabels[$case->priority] ?? $case->priority }} PRIORITAET
                                    </td>
                                    <td style="text-align: right; color: #6B7280; font-size: 12px;">
                                        {{ $case->created_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- SLA Warning Banner (if overdue or at risk) --}}
                    @if($isResponseOverdue || $isResolutionOverdue)
                    <tr>
                        <td style="padding: 16px 24px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FEE2E2; border: 2px solid #DC2626; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="30" style="vertical-align: top; color: #DC2626; font-size: 18px;">!!!</td>
                                                <td style="color: #DC2626; font-weight: 600; font-size: 13px;">
                                                    @if($isResponseOverdue && $isResolutionOverdue)
                                                        SLA-VERLETZUNG: Reaktions- und Loesungszeit ueberschritten!
                                                    @elseif($isResponseOverdue)
                                                        SLA-WARNUNG: Erste Reaktionszeit ueberschritten!
                                                    @else
                                                        SLA-WARNUNG: Loesungszeit ueberschritten!
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @elseif($isAtRisk)
                    <tr>
                        <td style="padding: 16px 24px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FEF3C7; border: 2px solid #F59E0B; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="30" style="vertical-align: top; color: #D97706; font-size: 18px;">!!</td>
                                                <td style="color: #D97706; font-weight: 600; font-size: 13px;">
                                                    SLA GEFAEHRDET: Nur noch {{ now()->diffInMinutes($case->sla_response_due_at) }} Minuten bis zur ersten Reaktion!
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- Hero Ticket ID --}}
                    <tr>
                        <td style="text-align: center; padding: 32px 24px 16px;">
                            <div style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 28px; font-weight: 700; color: #1F2937; letter-spacing: 1px;">
                                {{ $case->formatted_id }}
                            </div>
                            <div style="color: #6B7280; font-size: 12px; margin-top: 4px;">Ticket-Nummer</div>
                        </td>
                    </tr>

                    {{-- Badge Cluster --}}
                    <tr>
                        <td style="text-align: center; padding: 0 24px 24px;">
                            {{-- Status Badge --}}
                            <span style="display: inline-block; background-color: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
                                {{ $statusLabels[$case->status] ?? $case->status }}
                            </span>
                            {{-- Type Badge --}}
                            <span style="display: inline-block; background-color: {{ $tColor['bg'] }}; color: {{ $tColor['text'] }}; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
                                {{ $typeLabels[$case->case_type] ?? $case->case_type }}
                            </span>
                            {{-- Category Badge --}}
                            @if($case->category)
                            <span style="display: inline-block; background-color: #F3F4F6; color: #374151; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
                                {{ $case->category->name }}
                            </span>
                            @endif
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding: 0 24px;">
                            <div style="border-top: 1px solid #E5E7EB;"></div>
                        </td>
                    </tr>

                    {{-- Subject --}}
                    <tr>
                        <td style="padding: 24px 24px 8px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">BETREFF</div>
                            <div style="color: #1F2937; font-size: 18px; font-weight: 600;">{{ $case->subject }}</div>
                        </td>
                    </tr>

                    {{-- Description --}}
                    <tr>
                        <td style="padding: 16px 24px 24px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">BESCHREIBUNG</div>
                            <div style="background-color: #F9FAFB; border-radius: 8px; padding: 16px; color: #374151; font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $case->description }}</div>
                        </td>
                    </tr>

                    {{-- Two-Column Info Cards --}}
                    <tr>
                        <td style="padding: 0 24px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    {{-- Customer Card (Blue) --}}
                                    <td class="stack-column" width="48%" style="vertical-align: top;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="info-card" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <div style="color: #1D4ED8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                                                        [+] KONTAKT / KUNDE
                                                    </div>
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Name</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{ $customerName }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Telefon</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">
                                                                <a href="tel:{{ $customerPhone }}" style="color: #1F2937; text-decoration: none;">{{ $customerPhone }}</a>
                                                            </td>
                                                        </tr>
                                                        @if($customerEmail)
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px;">E-Mail</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right;">
                                                                <a href="mailto:{{ $customerEmail }}" style="color: #1D4ED8; text-decoration: none;">{{ $customerEmail }}</a>
                                                            </td>
                                                        </tr>
                                                        @endif
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <td width="4%" style="min-width: 16px;">&nbsp;</td>

                                    {{-- Call Card (Purple) --}}
                                    <td class="stack-column" width="48%" style="vertical-align: top;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="info-card" style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <div style="color: #7C3AED; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                                                        [#] ANRUF-DETAILS
                                                    </div>
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        {{-- Servicenummer (angerufene Nummer) --}}
                                                        @if($serviceNumber)
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Servicenummer</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">
                                                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $serviceNumber) }}" style="color: #1F2937; text-decoration: none;">{{ $serviceNumber }}</a>
                                                            </td>
                                                        </tr>
                                                        @endif
                                                        {{-- Zugehoeriges Unternehmen/Filiale --}}
                                                        @if($receiverDisplay)
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Zugehoeriges Unternehmen</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{ $receiverDisplay }}</td>
                                                        </tr>
                                                        @endif
                                                        {{-- Gespraechsdauer --}}
                                                        @if($callDuration)
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Gespraechsdauer</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{ $callDuration }}</td>
                                                        </tr>
                                                        @endif
                                                        @if($retellCallId)
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Anruf-ID</td>
                                                            <td style="color: #1F2937; font-size: 11px; font-weight: 500; text-align: right; padding-bottom: 6px; font-family: 'SF Mono', Monaco, 'Courier New', monospace;">{{ Str::limit($retellCallId, 14) }}</td>
                                                        </tr>
                                                        @endif
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Datum</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{ $case->call?->created_at?->timezone('Europe/Berlin')->format('d.m.Y') ?? $case->created_at->timezone('Europe/Berlin')->format('d.m.Y') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px;">Uhrzeit</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right;">{{ $case->call?->created_at?->timezone('Europe/Berlin')->format('H:i') ?? $case->created_at->timezone('Europe/Berlin')->format('H:i') }} Uhr</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Additional Context Card (Orange) - only if ai_metadata has extra fields --}}
                    @if($customerLocation || $othersAffected !== null || $problemSince)
                    <tr>
                        <td style="padding: 0 24px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFF7ED; border: 1px solid #FED7AA; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #C2410C; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                                            [i] ZUSAETZLICHE INFORMATIONEN
                                        </div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            @if($customerLocation)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px; width: 140px;">Standort/Buero</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; padding-bottom: 6px;">{{ $customerLocation }}</td>
                                            </tr>
                                            @endif
                                            @if($problemSince)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px; width: 140px;">Problem seit</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500; padding-bottom: 6px;">{{ $problemSince }}</td>
                                            </tr>
                                            @endif
                                            @if($othersAffected !== null)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; width: 140px;">Andere betroffen?</td>
                                                <td style="color: #1F2937; font-size: 13px; font-weight: 500;">
                                                    @if($othersAffected === true || $othersAffected === 'true' || $othersAffected === 1)
                                                        <span style="color: #DC2626; font-weight: 600;">Ja</span>
                                                        @if(is_string($othersAffected) && strlen($othersAffected) > 4)
                                                            - {{ $othersAffected }}
                                                        @endif
                                                    @elseif($othersAffected === false || $othersAffected === 'false' || $othersAffected === 0)
                                                        Nein, nur diese Person
                                                    @else
                                                        {{ $othersAffected }}
                                                    @endif
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- AI Analysis Card (Green) --}}
                    @if($aiSummary)
                    <tr>
                        <td style="padding: 0 24px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #059669; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                            [*] KI-ANALYSE
                                        </div>
                                        <div style="color: #374151; font-size: 13px; line-height: 1.6;">{{ $aiSummary }}</div>
                                        @if($confidence)
                                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #A7F3D0;">
                                            <span style="color: #6B7280; font-size: 11px;">Konfidenz:</span>
                                            <span style="color: #059669; font-size: 11px; font-weight: 600;">{{ is_numeric($confidence) ? number_format($confidence * 100, 0) . '%' : $confidence }}</span>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- SLA Information Card (Yellow) --}}
                    @if($case->sla_response_due_at || $case->sla_resolution_due_at)
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #B45309; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                                            [T] SLA-FRISTEN
                                        </div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                            @if($case->sla_response_due_at)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Erste Reaktion bis</td>
                                                <td style="font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px; {{ $isResponseOverdue ? 'color: #DC2626;' : 'color: #1F2937;' }}">
                                                    {{ $case->sla_response_due_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                                    @if($isResponseOverdue) (UEBERFAELLIG!) @endif
                                                </td>
                                            </tr>
                                            @endif
                                            @if($case->sla_resolution_due_at)
                                            <tr>
                                                <td style="color: #6B7280; font-size: 12px;">Loesung bis</td>
                                                <td style="font-size: 13px; font-weight: 500; text-align: right; {{ $isResolutionOverdue ? 'color: #DC2626;' : 'color: #1F2937;' }}">
                                                    {{ $case->sla_resolution_due_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                                    @if($isResolutionOverdue) (UEBERFAELLIG!) @endif
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- Action Button --}}
                    <tr>
                        <td class="button-td" style="text-align: center; padding: 8px 24px 32px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 8px; background-color: #2563EB;">
                                        <a class="button-a" href="{{ config('app.url') }}/admin/service-cases/{{ $case->id }}" style="display: inline-block; background-color: #2563EB; color: #ffffff; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; border: 1px solid #2563EB;">
                                            TICKET BEARBEITEN -->
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 16px 24px; border-top: 1px solid #E5E7EB;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #9CA3AF; font-size: 11px;">
                                        Gesendet: {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                    </td>
                                    <td style="text-align: right; color: #9CA3AF; font-size: 11px;">
                                        {{ $case->company?->name ?? config('app.name') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @else
                    {{-- ================================================================ --}}
                    {{-- CUSTOMER VERSION - Friendly confirmation for the caller        --}}
                    {{-- ================================================================ --}}

                    {{-- Header --}}
                    <tr>
                        <td style="text-align: center; padding: 40px 24px 24px;">
                            <div style="font-size: 48px; margin-bottom: 16px;">[OK]</div>
                            <h1 style="color: #1F2937; font-size: 24px; font-weight: 700; margin: 0 0 8px 0;">Ihre Anfrage wurde erfasst</h1>
                            <p style="color: #6B7280; font-size: 14px; margin: 0;">Vielen Dank fuer Ihre Kontaktaufnahme.</p>
                        </td>
                    </tr>

                    {{-- Ticket Number Card --}}
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F0FDF4; border: 2px solid #86EFAC; border-radius: 12px;">
                                <tr>
                                    <td style="text-align: center; padding: 24px;">
                                        <div style="color: #6B7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Ihre Ticket-Nummer</div>
                                        <div style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 28px; font-weight: 700; color: #059669; letter-spacing: 2px;">
                                            {{ $case->formatted_id }}
                                        </div>
                                        <div style="color: #6B7280; font-size: 12px; margin-top: 8px;">Bitte bei Rueckfragen angeben</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Subject Summary --}}
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F9FAFB; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Ihr Anliegen</div>
                                        <div style="color: #1F2937; font-size: 16px; font-weight: 500;">{{ $case->subject }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Next Steps --}}
                    <tr>
                        <td style="padding: 0 24px 32px;">
                            <h3 style="color: #1F2937; font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Was passiert als naechstes?</h3>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding-bottom: 12px; width: 36px; vertical-align: top;">
                                        <div style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600;">1</div>
                                    </td>
                                    <td style="padding-bottom: 12px; color: #374151; font-size: 14px; vertical-align: middle;">
                                        Unser Team prueft Ihre Anfrage
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 12px; width: 36px; vertical-align: top;">
                                        <div style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600;">2</div>
                                    </td>
                                    <td style="padding-bottom: 12px; color: #374151; font-size: 14px; vertical-align: middle;">
                                        Sie erhalten eine Rueckmeldung per Telefon oder E-Mail
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 36px; vertical-align: top;">
                                        <div style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600;">3</div>
                                    </td>
                                    <td style="color: #374151; font-size: 14px; vertical-align: middle;">
                                        Wir loesen Ihr Anliegen schnellstmoeglich
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding: 0 24px;">
                            <div style="border-top: 1px solid #E5E7EB;"></div>
                        </td>
                    </tr>

                    {{-- Signature --}}
                    <tr>
                        <td style="text-align: center; padding: 24px;">
                            <p style="color: #6B7280; font-size: 14px; margin: 0 0 8px 0;">Mit freundlichen Gruessen,</p>
                            <p style="color: #1F2937; font-size: 14px; font-weight: 600; margin: 0;">{{ $case->company?->name ?? config('app.name') }}</p>
                        </td>
                    </tr>

                    @endif
                </table>

                {{-- Footer Text --}}
                <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
                    <tr>
                        <td style="text-align: center; color: #9CA3AF; font-size: 11px;">
                            Automatische Benachrichtigung von {{ config('app.name') }}
                            <br>
                            <span style="color: #D1D5DB;">Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese Nachricht.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
