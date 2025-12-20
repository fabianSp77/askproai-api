<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isInternal ? 'Neues Support-Ticket' : 'Ihre Anfrage wurde erfasst' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    @if($isInternal)
                    {{-- ════════════════════════════════════════════════════════════════ --}}
                    {{-- INTERNAL VERSION --}}
                    {{-- ════════════════════════════════════════════════════════════════ --}}
                    @php
                        $priorityColors = [
                            'critical' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'border' => '#DC2626'],
                            'high' => ['bg' => '#FEF3C7', 'text' => '#D97706', 'border' => '#F59E0B'],
                            'normal' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'border' => '#3B82F6'],
                            'low' => ['bg' => '#F3F4F6', 'text' => '#4B5563', 'border' => '#6B7280'],
                        ];
                        $pColor = $priorityColors[$case->priority] ?? $priorityColors['normal'];

                        $statusColors = [
                            'new' => ['bg' => '#F3F4F6', 'text' => '#374151'],
                            'open' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
                            'pending' => ['bg' => '#FEF3C7', 'text' => '#D97706'],
                            'resolved' => ['bg' => '#D1FAE5', 'text' => '#059669'],
                            'closed' => ['bg' => '#E0E7FF', 'text' => '#4338CA'],
                        ];
                        $sColor = $statusColors[$case->status] ?? $statusColors['new'];

                        $typeColors = [
                            'incident' => ['bg' => '#FEE2E2', 'text' => '#DC2626'],
                            'request' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
                            'inquiry' => ['bg' => '#F3E8FF', 'text' => '#7C3AED'],
                        ];
                        $tColor = $typeColors[$case->case_type] ?? $typeColors['request'];

                        $priorityLabels = ['critical' => 'Kritisch', 'high' => 'Hoch', 'normal' => 'Normal', 'low' => 'Niedrig'];
                        $statusLabels = ['new' => 'Neu', 'open' => 'Offen', 'pending' => 'Wartend', 'resolved' => 'Gelöst', 'closed' => 'Geschlossen'];
                        $typeLabels = ['incident' => 'Störung', 'request' => 'Anfrage', 'inquiry' => 'Rückfrage'];

                        $isOverdue = false;
                        if ($case->priority === 'critical' && $case->created_at->diffInMinutes(now()) > 60 && !in_array($case->status, ['resolved', 'closed'])) {
                            $isOverdue = true;
                        } elseif ($case->priority === 'high' && $case->created_at->diffInMinutes(now()) > 240 && !in_array($case->status, ['resolved', 'closed'])) {
                            $isOverdue = true;
                        }
                    @endphp

                    {{-- Priority Header --}}
                    <tr>
                        <td style="background-color: {{ $pColor['bg'] }}; border-left: 5px solid {{ $pColor['border'] }}; padding: 16px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: {{ $pColor['text'] }}; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        {{ $priorityLabels[$case->priority] ?? $case->priority }} Priorität
                                    </td>
                                    <td style="text-align: right; color: #6B7280; font-size: 12px;">
                                        {{ $case->created_at->format('d.m.Y H:i') }} Uhr
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if($isOverdue)
                    {{-- SLA Warning --}}
                    <tr>
                        <td style="padding: 16px 24px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FEE2E2; border: 2px solid #DC2626; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 12px 16px; color: #DC2626; font-weight: 600; font-size: 13px;">
                                        ⚠️ SLA-Warnung: Bearbeitungszeit überschritten!
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
                            <span style="display: inline-block; background-color: {{ $sColor['bg'] }}; color: {{ $sColor['text'] }}; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
                                {{ $statusLabels[$case->status] ?? $case->status }}
                            </span>
                            <span style="display: inline-block; background-color: {{ $tColor['bg'] }}; color: {{ $tColor['text'] }}; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
                                {{ $typeLabels[$case->case_type] ?? $case->case_type }}
                            </span>
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
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Betreff</div>
                            <div style="color: #1F2937; font-size: 18px; font-weight: 600;">{{ $case->subject }}</div>
                        </td>
                    </tr>

                    {{-- Description --}}
                    <tr>
                        <td style="padding: 16px 24px 24px;">
                            <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Beschreibung</div>
                            <div style="background-color: #F9FAFB; border-radius: 8px; padding: 16px; color: #374151; font-size: 14px; line-height: 1.6;">{{ $case->description }}</div>
                        </td>
                    </tr>

                    {{-- Info Cards --}}
                    @if($case->customer || $case->call)
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    @if($case->customer)
                                    {{-- Customer Card (Blue) --}}
                                    <td width="{{ $case->call ? '48%' : '100%' }}" style="vertical-align: top;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <div style="color: #1D4ED8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                                                        👤 Kunde
                                                    </div>
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Name</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{ $case->customer->name ?? 'Unbekannt' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Telefon</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{ $case->customer->phone ?? '-' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px;">E-Mail</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right;">{{ $case->customer->email ?? '-' }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    @if($case->call)<td width="4%"></td>@endif
                                    @endif

                                    @if($case->call)
                                    {{-- Call Card (Purple) --}}
                                    <td width="{{ $case->customer ? '48%' : '100%' }}" style="vertical-align: top;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 8px;">
                                            <tr>
                                                <td style="padding: 16px;">
                                                    <div style="color: #7C3AED; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                                                        📞 Anruf
                                                    </div>
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Anruf-ID</td>
                                                            <td style="color: #1F2937; font-size: 11px; font-weight: 500; text-align: right; padding-bottom: 6px; font-family: monospace;">{{ Str::limit($case->call->retell_call_id ?? $case->call_id, 12) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px; padding-bottom: 6px;">Datum</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right; padding-bottom: 6px;">{{ $case->call->created_at?->format('d.m.Y') ?? '-' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="color: #6B7280; font-size: 12px;">Uhrzeit</td>
                                                            <td style="color: #1F2937; font-size: 13px; font-weight: 500; text-align: right;">{{ $case->call->created_at?->format('H:i') ?? '-' }} Uhr</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- AI Summary Card (Green) --}}
                    @if($case->ai_summary)
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 16px;">
                                        <div style="color: #059669; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                            🤖 KI-Analyse
                                        </div>
                                        <div style="color: #374151; font-size: 13px; line-height: 1.5;">{{ $case->ai_summary }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    {{-- Action Button --}}
                    <tr>
                        <td style="text-align: center; padding: 8px 24px 32px;">
                            <a href="{{ config('app.url') }}/admin/service-cases/{{ $case->id }}" style="display: inline-block; background-color: #2563EB; color: #ffffff; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;">
                                Ticket bearbeiten
                            </a>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 16px 24px; border-top: 1px solid #E5E7EB;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="color: #9CA3AF; font-size: 11px;">
                                        Gesendet: {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
                                    </td>
                                    <td style="text-align: right; color: #9CA3AF; font-size: 11px;">
                                        {{ $case->company->name ?? config('app.name') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @else
                    {{-- ════════════════════════════════════════════════════════════════ --}}
                    {{-- CUSTOMER VERSION --}}
                    {{-- ════════════════════════════════════════════════════════════════ --}}

                    {{-- Header --}}
                    <tr>
                        <td style="text-align: center; padding: 40px 24px 24px;">
                            <div style="font-size: 48px; margin-bottom: 16px;">✅</div>
                            <h1 style="color: #1F2937; font-size: 24px; font-weight: 700; margin: 0 0 8px 0;">Ihre Anfrage wurde erfasst</h1>
                            <p style="color: #6B7280; font-size: 14px; margin: 0;">Vielen Dank für Ihre Kontaktaufnahme.</p>
                        </td>
                    </tr>

                    {{-- Ticket Number Card --}}
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F0FDF4; border: 2px solid #86EFAC; border-radius: 12px;">
                                <tr>
                                    <td style="text-align: center; padding: 24px;">
                                        <div style="color: #6B7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Ihre Ticket-Nummer</div>
                                        <div style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 28px; font-weight: 700; color: #059669; letter-spacing: 2px;">
                                            {{ $case->formatted_id }}
                                        </div>
                                        <div style="color: #6B7280; font-size: 12px; margin-top: 8px;">Bitte bei Rückfragen angeben</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Subject Summary --}}
                    <tr>
                        <td style="padding: 0 24px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #F9FAFB; border-radius: 8px;">
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
                            <h3 style="color: #1F2937; font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Was passiert als nächstes?</h3>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding-bottom: 12px; width: 36px; vertical-align: top;">
                                        <div style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600;">1</div>
                                    </td>
                                    <td style="padding-bottom: 12px; color: #374151; font-size: 14px; vertical-align: middle;">
                                        Unser Team prüft Ihre Anfrage
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 12px; width: 36px; vertical-align: top;">
                                        <div style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600;">2</div>
                                    </td>
                                    <td style="padding-bottom: 12px; color: #374151; font-size: 14px; vertical-align: middle;">
                                        Sie erhalten eine Rückmeldung per Telefon oder E-Mail
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 36px; vertical-align: top;">
                                        <div style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600;">3</div>
                                    </td>
                                    <td style="color: #374151; font-size: 14px; vertical-align: middle;">
                                        Wir lösen Ihr Anliegen schnellstmöglich
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
                            <p style="color: #6B7280; font-size: 14px; margin: 0 0 8px 0;">Mit freundlichen Grüßen,</p>
                            <p style="color: #1F2937; font-size: 14px; font-weight: 600; margin: 0;">{{ $case->company?->name ?? config('app.name') }}</p>
                        </td>
                    </tr>

                    @endif
                </table>

                {{-- Footer Text --}}
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
                    <tr>
                        <td style="text-align: center; color: #9CA3AF; font-size: 11px;">
                            Automatische Benachrichtigung von AskProAI
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
