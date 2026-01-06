@php
    // German translations
    $priorityLabels = ['critical' => 'KRITISCH', 'high' => 'HOCH', 'normal' => 'NORMAL', 'low' => 'NIEDRIG'];
    $statusLabels = ['new' => 'Neu', 'open' => 'Offen', 'pending' => 'Wartend', 'resolved' => 'Geloest', 'closed' => 'Geschlossen'];
    $typeLabels = ['incident' => 'Stoerung', 'request' => 'Anfrage', 'inquiry' => 'Rueckfrage'];

    // SLA calculations
    $isResponseOverdue = $case->sla_response_due_at && now()->isAfter($case->sla_response_due_at) && !in_array($case->status, ['resolved', 'closed']);
    $isResolutionOverdue = $case->sla_resolution_due_at && now()->isAfter($case->sla_resolution_due_at) && !in_array($case->status, ['resolved', 'closed']);

    // Fallback SLA based on priority
    if (!$case->sla_response_due_at && $case->priority === 'critical') {
        $isResponseOverdue = $case->created_at->diffInMinutes(now()) > 60;
    } elseif (!$case->sla_response_due_at && $case->priority === 'high') {
        $isResponseOverdue = $case->created_at->diffInMinutes(now()) > 240;
    }

    // Extract ai_metadata fields
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

    // Priority indicator
    $priorityIndicator = match($case->priority) {
        'critical' => '[!!!]',
        'high' => '[!!]',
        'normal' => '[!]',
        default => '[-]',
    };
@endphp
@if($isInternal)
================================================================================
                         IT SUPPORT TICKET BENACHRICHTIGUNG
================================================================================

{{ $priorityIndicator }} PRIORITAET: {{ $priorityLabels[$case->priority] ?? strtoupper($case->priority) }}
@if($isResponseOverdue || $isResolutionOverdue)

!!! SLA-WARNUNG !!!
@if($isResponseOverdue && $isResolutionOverdue)
Die Reaktions- und Loesungszeit wurde ueberschritten!
@elseif($isResponseOverdue)
Die erste Reaktionszeit wurde ueberschritten!
@else
Die Loesungszeit wurde ueberschritten!
@endif
@endif

--------------------------------------------------------------------------------
TICKET-NUMMER:  {{ $case->formatted_id }}
--------------------------------------------------------------------------------

Status:         {{ $statusLabels[$case->status] ?? $case->status }}
Typ:            {{ $typeLabels[$case->case_type] ?? $case->case_type }}
@if($case->category)
Kategorie:      {{ $case->category->name }}
@endif
Erstellt am:    {{ $case->created_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr

================================================================================
BETREFF
================================================================================

{{ $case->subject }}

================================================================================
BESCHREIBUNG
================================================================================

{{ $case->description }}

================================================================================
KONTAKT / KUNDE
================================================================================

Name:           {{ $customerName }}
Telefon:        {{ $customerPhone }}
@if($customerEmail)
E-Mail:         {{ $customerEmail }}
@endif
@if($customerLocation)
Standort:       {{ $customerLocation }}
@endif

================================================================================
ANRUF-DETAILS
================================================================================

@if($serviceNumber)
Servicenummer:  {{ $serviceNumber }}
@endif
@if($receiverDisplay)
Zugehoeriges Unternehmen: {{ $receiverDisplay }}
@endif
@if($callDuration)
Gespraechsdauer: {{ $callDuration }}
@endif
@if($retellCallId)
Anruf-ID:       {{ $retellCallId }}
@endif
Datum:          {{ $case->call?->created_at?->timezone('Europe/Berlin')->format('d.m.Y') ?? $case->created_at->timezone('Europe/Berlin')->format('d.m.Y') }}
Uhrzeit:        {{ $case->call?->created_at?->timezone('Europe/Berlin')->format('H:i') ?? $case->created_at->timezone('Europe/Berlin')->format('H:i') }} Uhr
@if($problemSince || $othersAffected !== null)

================================================================================
ZUSAETZLICHE INFORMATIONEN
================================================================================

@if($problemSince)
Problem seit:   {{ $problemSince }}
@endif
@if($othersAffected !== null)
Andere betroffen: @if($othersAffected === true || $othersAffected === 'true' || $othersAffected === 1)Ja @if(is_string($othersAffected) && strlen($othersAffected) > 4)- {{ $othersAffected }}@endif
@elseif($othersAffected === false || $othersAffected === 'false' || $othersAffected === 0)Nein @else{{ $othersAffected }}@endif
@endif
@endif
@if($aiSummary)

================================================================================
KI-ANALYSE
================================================================================

{{ $aiSummary }}
@if($confidence)

Konfidenz:      {{ is_numeric($confidence) ? number_format($confidence * 100, 0) . '%' : $confidence }}
@endif
@endif
@if($case->sla_response_due_at || $case->sla_resolution_due_at)

================================================================================
SLA-FRISTEN
================================================================================

@if($case->sla_response_due_at)
Erste Reaktion bis:  {{ $case->sla_response_due_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr @if($isResponseOverdue)(UEBERFAELLIG!)@endif

@endif
@if($case->sla_resolution_due_at)
Loesung bis:         {{ $case->sla_resolution_due_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr @if($isResolutionOverdue)(UEBERFAELLIG!)@endif

@endif
@endif

================================================================================
AKTION ERFORDERLICH
================================================================================

Ticket bearbeiten:
{{ config('app.url') }}/admin/service-cases/{{ $case->id }}

--------------------------------------------------------------------------------
Gesendet: {{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
{{ $case->company?->name ?? config('app.name') }}

Diese E-Mail wurde automatisch generiert.
Bitte antworten Sie nicht direkt auf diese Nachricht.
================================================================================
@else
================================================================================
                    BESTAETIGUNG IHRER ANFRAGE
================================================================================

Sehr geehrte/r {{ $customerName }},

vielen Dank fuer Ihre Kontaktaufnahme. Ihre Anfrage wurde erfolgreich
erfasst und wird von unserem Team bearbeitet.

--------------------------------------------------------------------------------
IHRE TICKET-NUMMER:  {{ $case->formatted_id }}
--------------------------------------------------------------------------------

Bitte geben Sie diese Nummer bei allen Rueckfragen an.

IHR ANLIEGEN:
{{ $case->subject }}

--------------------------------------------------------------------------------
WAS PASSIERT ALS NAECHSTES?
--------------------------------------------------------------------------------

1. Unser Team prueft Ihre Anfrage
2. Sie erhalten eine Rueckmeldung per Telefon oder E-Mail
3. Wir loesen Ihr Anliegen schnellstmoeglich

--------------------------------------------------------------------------------

Mit freundlichen Gruessen,
{{ $case->company?->name ?? config('app.name') }}

--------------------------------------------------------------------------------
Diese E-Mail wurde automatisch generiert.
Bitte antworten Sie nicht direkt auf diese Nachricht.
================================================================================
@endif
