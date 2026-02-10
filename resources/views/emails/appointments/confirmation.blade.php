@component('mail::message')
# Terminbestätigung

Hallo {{ $appointment->customer?->name ?? 'Kunde' }},

vielen Dank für Ihre Buchung. Hiermit bestätigen wir Ihren Termin:

@component('mail::panel')
**Service:** {{ $appointment->service?->name ?? 'Service' }}<br>
**Datum:** {{ \Carbon\Carbon::parse($appointment->starts_at)->locale('de')->format('l, d. F Y') }}<br>
**Uhrzeit:** {{ \Carbon\Carbon::parse($appointment->starts_at)->format('H:i') }} - {{ \Carbon\Carbon::parse($appointment->ends_at)->format('H:i') }} Uhr<br>
**Ort:** {{ $appointment->branch?->name ?? 'Filiale' }}<br>
{{ $appointment->branch?->address ?? '' }}
@endcomponent

@isset($appointment->is_composite)
@if($appointment->is_composite && !empty($appointment->segments))
## Ihre Terminübersicht

@foreach($appointment->segments as $segment)
**{{ $segment['name'] ?? 'Segment' }}**<br>
{{ \Carbon\Carbon::parse($segment['starts_at'])->format('H:i') }} - {{ \Carbon\Carbon::parse($segment['ends_at'])->format('H:i') }} Uhr
@if(isset($segment['staff_name']))
mit {{ $segment['staff_name'] }}
@endif
<br><br>
@endforeach
@endif
@endisset

## Wichtige Hinweise

- Bitte erscheinen Sie pünktlich zu Ihrem Termin
- Bei Verhinderung bitten wir um rechtzeitige Absage
- Ihre Bestätigungsnummer: **{{ substr($appointment->composite_group_uid ?? $appointment->id, 0, 8) }}**

@if($includeIcs ?? false)
Den Termin können Sie mit der angehängten ICS-Datei direkt in Ihren Kalender importieren.
@endif

@component('mail::button', ['url' => config('app.url') . '/appointments/' . $appointment->id])
Termin anzeigen
@endcomponent

Bei Fragen stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen,<br>
{{ $appointment->company?->name ?? config('app.name') }}
@endcomponent