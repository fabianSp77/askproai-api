@component('mail::message')
# Terminabsage

Hallo {{ $appointment->customer->name }},

hiermit bestätigen wir die Absage Ihres Termins:

@component('mail::panel')
**Service:** {{ $appointment->service->name }}<br>
**Ursprünglicher Termin:** {{ \Carbon\Carbon::parse($appointment->starts_at)->locale('de')->format('l, d. F Y') }}<br>
**Uhrzeit:** {{ \Carbon\Carbon::parse($appointment->starts_at)->format('H:i') }} - {{ \Carbon\Carbon::parse($appointment->ends_at)->format('H:i') }} Uhr<br>
**Ort:** {{ $appointment->branch->name }}
@endcomponent

@if($appointment->is_composite)
Der gesamte Termin mit allen Behandlungsabschnitten wurde storniert.
@endif

@if(isset($reason) && $reason)
**Grund der Absage:** {{ $reason }}
@endif

## Neue Terminvereinbarung

Gerne können Sie einen neuen Termin vereinbaren:

@component('mail::button', ['url' => config('app.url') . '/booking'])
Neuen Termin buchen
@endcomponent

Alternativ erreichen Sie uns auch telefonisch unter {{ $appointment->branch->phone ?? 'unserer Servicenummer' }}.

Wir bedauern die Unannehmlichkeiten und freuen uns darauf, Sie bald wieder bei uns begrüßen zu dürfen.

Mit freundlichen Grüßen,<br>
{{ $appointment->company->name ?? config('app.name') }}
@endcomponent