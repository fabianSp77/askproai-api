@component('mail::message')
# Terminerinnerung

Hallo {{ $appointment->customer->name }},

wir möchten Sie an Ihren bevorstehenden Termin erinnern:

@component('mail::panel')
**Service:** {{ $appointment->service->name }}<br>
**Datum:** {{ \Carbon\Carbon::parse($appointment->starts_at)->locale('de')->format('l, d. F Y') }}<br>
**Uhrzeit:** {{ \Carbon\Carbon::parse($appointment->starts_at)->format('H:i') }} - {{ \Carbon\Carbon::parse($appointment->ends_at)->format('H:i') }} Uhr<br>
**Ort:** {{ $appointment->branch->name }}<br>
{{ $appointment->branch->address }}
@endcomponent

@if($appointment->is_composite && !empty($appointment->segments))
## Ihre Terminübersicht

@foreach($appointment->segments as $segment)
**{{ $segment['name'] }}**<br>
{{ \Carbon\Carbon::parse($segment['starts_at'])->format('H:i') }} - {{ \Carbon\Carbon::parse($segment['ends_at'])->format('H:i') }} Uhr
@if(isset($segment['staff_name']))
mit {{ $segment['staff_name'] }}
@endif
<br><br>
@endforeach
@endif

## Wichtige Hinweise

@php
$hoursUntil = \Carbon\Carbon::now()->diffInHours($appointment->starts_at);
@endphp

@if($hoursUntil <= 24)
**Ihr Termin findet {{ $hoursUntil <= 2 ? 'in Kürze' : 'morgen' }} statt!**
@else
**Ihr Termin findet in {{ $hoursUntil }} Stunden statt.**
@endif

- Bitte erscheinen Sie pünktlich
- Falls Sie verhindert sein sollten, informieren Sie uns bitte umgehend
- Ihre Bestätigungsnummer: **{{ substr($appointment->composite_group_uid ?? $appointment->id, 0, 8) }}**

## Anfahrt

@if($appointment->branch->parking_info)
**Parkmöglichkeiten:** {{ $appointment->branch->parking_info }}
@endif

@if($appointment->branch->public_transport)
**Öffentliche Verkehrsmittel:** {{ $appointment->branch->public_transport }}
@endif

@component('mail::button', ['url' => config('app.url') . '/appointments/' . $appointment->id])
Termindetails anzeigen
@endcomponent

Falls Sie diesen Termin absagen oder verschieben müssen, kontaktieren Sie uns bitte rechtzeitig.

Wir freuen uns auf Ihren Besuch!

Mit freundlichen Grüßen,<br>
{{ $appointment->company->name ?? config('app.name') }}
@endcomponent