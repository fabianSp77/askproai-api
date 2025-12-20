<x-mail::message>
@php
    // Priority colors
    $priorityColors = [
        'critical' => ['bg' => '#FEE2E2', 'text' => '#DC2626', 'border' => '#DC2626'],
        'high' => ['bg' => '#FEF3C7', 'text' => '#D97706', 'border' => '#F59E0B'],
        'normal' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'border' => '#3B82F6'],
        'low' => ['bg' => '#F3F4F6', 'text' => '#4B5563', 'border' => '#6B7280'],
    ];
    $priorityColor = $priorityColors[$case->priority] ?? $priorityColors['normal'];

    // Status colors
    $statusColors = [
        'new' => ['bg' => '#F3F4F6', 'text' => '#374151'],
        'open' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
        'pending' => ['bg' => '#FEF3C7', 'text' => '#D97706'],
        'resolved' => ['bg' => '#D1FAE5', 'text' => '#059669'],
        'closed' => ['bg' => '#E0E7FF', 'text' => '#4338CA'],
    ];
    $statusColor = $statusColors[$case->status] ?? $statusColors['new'];

    // Type colors
    $typeColors = [
        'incident' => ['bg' => '#FEE2E2', 'text' => '#DC2626'],
        'request' => ['bg' => '#DBEAFE', 'text' => '#1D4ED8'],
        'inquiry' => ['bg' => '#F3E8FF', 'text' => '#7C3AED'],
    ];
    $typeColor = $typeColors[$case->case_type] ?? $typeColors['request'];

    // German translations
    $priorityLabels = ['critical' => 'Kritisch', 'high' => 'Hoch', 'normal' => 'Normal', 'low' => 'Niedrig'];
    $statusLabels = ['new' => 'Neu', 'open' => 'Offen', 'pending' => 'Wartend', 'resolved' => 'Gelöst', 'closed' => 'Geschlossen'];
    $typeLabels = ['incident' => 'Störung', 'request' => 'Anfrage', 'inquiry' => 'Rückfrage'];

    // SLA calculation (if applicable)
    $isOverdue = false;
    $slaMinutes = null;
    if ($case->priority === 'critical') {
        $slaMinutes = 60; // 1 hour
    } elseif ($case->priority === 'high') {
        $slaMinutes = 240; // 4 hours
    }
    if ($slaMinutes && $case->created_at->diffInMinutes(now()) > $slaMinutes && !in_array($case->status, ['resolved', 'closed'])) {
        $isOverdue = true;
    }
@endphp

@if($isInternal)
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- INTERNAL VERSION - Full technical details for support staff --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}

{{-- SLA Warning Banner (if overdue) --}}
@if($isOverdue)
<div style="background-color: #FEE2E2; border: 2px solid #DC2626; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="font-size: 20px; padding-right: 12px; vertical-align: middle;">⚠️</td>
            <td style="color: #DC2626; font-weight: 600; font-size: 14px;">
                SLA-Warnung: Dieses Ticket hat die Bearbeitungszeit überschritten!
            </td>
        </tr>
    </table>
</div>
@endif

{{-- Priority Header Bar --}}
<div style="background-color: {{ $priorityColor['bg'] }}; border-left: 4px solid {{ $priorityColor['border'] }}; padding: 16px 20px; margin-bottom: 24px; border-radius: 0 8px 8px 0;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td>
                <span style="color: {{ $priorityColor['text'] }}; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                    {{ $priorityLabels[$case->priority] ?? $case->priority }} Priorität
                </span>
            </td>
            <td style="text-align: right;">
                <span style="color: #6B7280; font-size: 12px;">
                    {{ $case->created_at->format('d.m.Y H:i') }} Uhr
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- Hero Ticket ID --}}
<div style="text-align: center; margin-bottom: 24px;">
    <span style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 28px; font-weight: 700; color: #1F2937; letter-spacing: 1px;">
        {{ $case->formatted_id }}
    </span>
    <div style="color: #6B7280; font-size: 12px; margin-top: 4px;">Ticket-Nummer</div>
</div>

{{-- Badge Cluster --}}
<div style="text-align: center; margin-bottom: 24px;">
    {{-- Status Badge --}}
    <span style="display: inline-block; background-color: {{ $statusColor['bg'] }}; color: {{ $statusColor['text'] }}; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
        {{ $statusLabels[$case->status] ?? $case->status }}
    </span>
    {{-- Type Badge --}}
    <span style="display: inline-block; background-color: {{ $typeColor['bg'] }}; color: {{ $typeColor['text'] }}; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
        {{ $typeLabels[$case->case_type] ?? $case->case_type }}
    </span>
    {{-- Category Badge --}}
    @if($case->category)
    <span style="display: inline-block; background-color: #F3F4F6; color: #374151; padding: 6px 14px; border-radius: 9999px; font-size: 12px; font-weight: 600; margin: 4px;">
        {{ $case->category->name }}
    </span>
    @endif
</div>

{{-- Divider --}}
<div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>

{{-- Subject --}}
<div style="margin-bottom: 20px;">
    <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Betreff</div>
    <div style="color: #1F2937; font-size: 18px; font-weight: 600;">{{ $case->subject }}</div>
</div>

{{-- Description --}}
<div style="margin-bottom: 24px;">
    <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Beschreibung</div>
    <div style="background-color: #F9FAFB; border-radius: 8px; padding: 16px; color: #374151; font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $case->description }}</div>
</div>

{{-- Info Cards Row --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 24px;">
    <tr>
        {{-- Customer Card (Blue) --}}
        @if($case->customer)
        <td width="48%" style="vertical-align: top;">
            <div style="background-color: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 16px;">
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
            </div>
        </td>
        <td width="4%"></td>
        @endif

        {{-- Call Card (Purple) --}}
        @if($case->call)
        <td width="48%" style="vertical-align: top;">
            <div style="background-color: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 8px; padding: 16px;">
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
            </div>
        </td>
        @endif
    </tr>
</table>

{{-- AI Data Card (Green) - if available --}}
@if($case->ai_summary || $case->metadata)
<div style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
    <div style="color: #059669; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
        🤖 KI-Analyse
    </div>
    @if($case->ai_summary)
    <div style="color: #374151; font-size: 13px; line-height: 1.5;">{{ $case->ai_summary }}</div>
    @endif
    @if($case->metadata && isset($case->metadata['confidence']))
    <div style="margin-top: 8px; color: #6B7280; font-size: 11px;">
        Konfidenz: {{ number_format($case->metadata['confidence'] * 100, 0) }}%
    </div>
    @endif
</div>
@endif

{{-- Action Button --}}
<div style="text-align: center; margin: 32px 0;">
<x-mail::button :url="config('app.url') . '/admin/service-cases/' . $case->id" color="primary">
Ticket bearbeiten
</x-mail::button>
</div>

{{-- Footer Info --}}
<div style="border-top: 1px solid #E5E7EB; padding-top: 16px; margin-top: 24px;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="color: #9CA3AF; font-size: 11px;">
                Erstellt: {{ $case->created_at->format('d.m.Y H:i') }} Uhr
            </td>
            <td style="text-align: right; color: #9CA3AF; font-size: 11px;">
                @if($case->company)
                {{ $case->company->name }}
                @endif
            </td>
        </tr>
    </table>
</div>

@else
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- CUSTOMER VERSION - Friendly confirmation for the caller --}}
{{-- ═══════════════════════════════════════════════════════════════════════════ --}}

<div style="text-align: center; margin-bottom: 32px;">
    <div style="font-size: 48px; margin-bottom: 16px;">✅</div>
    <h1 style="color: #1F2937; font-size: 24px; font-weight: 700; margin: 0 0 8px 0;">Ihre Anfrage wurde erfasst</h1>
    <p style="color: #6B7280; font-size: 14px; margin: 0;">Vielen Dank für Ihre Kontaktaufnahme.</p>
</div>

{{-- Ticket Number Card --}}
<div style="background-color: #F0FDF4; border: 2px solid #86EFAC; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;">
    <div style="color: #6B7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Ihre Ticket-Nummer</div>
    <div style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 32px; font-weight: 700; color: #059669; letter-spacing: 2px;">
        {{ $case->formatted_id }}
    </div>
    <div style="color: #6B7280; font-size: 12px; margin-top: 8px;">Bitte bei Rückfragen angeben</div>
</div>

{{-- Subject Summary --}}
<div style="background-color: #F9FAFB; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
    <div style="color: #6B7280; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Ihr Anliegen</div>
    <div style="color: #1F2937; font-size: 16px; font-weight: 500;">{{ $case->subject }}</div>
</div>

{{-- Next Steps --}}
<div style="margin-bottom: 32px;">
    <h3 style="color: #1F2937; font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Was passiert als nächstes?</h3>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td style="padding-bottom: 12px; vertical-align: top;">
                <span style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600; margin-right: 12px;">1</span>
            </td>
            <td style="padding-bottom: 12px; color: #374151; font-size: 14px;">
                Unser Team prüft Ihre Anfrage
            </td>
        </tr>
        <tr>
            <td style="padding-bottom: 12px; vertical-align: top;">
                <span style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600; margin-right: 12px;">2</span>
            </td>
            <td style="padding-bottom: 12px; color: #374151; font-size: 14px;">
                Sie erhalten eine Rückmeldung per Telefon oder E-Mail
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top;">
                <span style="display: inline-block; background-color: #DBEAFE; color: #1D4ED8; width: 24px; height: 24px; border-radius: 50%; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600; margin-right: 12px;">3</span>
            </td>
            <td style="color: #374151; font-size: 14px;">
                Wir lösen Ihr Anliegen schnellstmöglich
            </td>
        </tr>
    </table>
</div>

{{-- Divider --}}
<div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>

{{-- Signature --}}
<div style="text-align: center; color: #6B7280; font-size: 14px;">
    <p style="margin: 0 0 8px 0;">Mit freundlichen Grüßen,</p>
    <p style="margin: 0; color: #1F2937; font-weight: 600;">{{ $case->company?->name ?? config('app.name') }}</p>
</div>

@endif
</x-mail::message>
